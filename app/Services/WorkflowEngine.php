<?php
namespace App\Services;

use App\Models\Notification;
use App\Models\Task;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use PDO;

class WorkflowEngine {
    public function __construct(private PDO $db) {}

    public function onEvent(string $triggerKey, array $payload = []): int {
        $workflowModel = new Workflow($this->db);
        $count = 0;
        foreach ($workflowModel->activeByTrigger($triggerKey) as $workflow) {
            $this->enqueueWorkflow($workflow, $payload + ['trigger_key' => $triggerKey]);
            $count++;
        }
        return $count;
    }

    public function enqueueWorkflow(array $workflow, array $payload = []): int {
        $stmt = $this->db->prepare('INSERT INTO workflow_queue (company_id, workflow_id, trigger_key, payload_json, queue_status, available_at, created_at) VALUES (?,?,?,?,?,?,NOW())');
        $stmt->execute([
            current_company_id(),
            $workflow['id'],
            $workflow['trigger_key'],
            json_encode($payload, JSON_UNESCAPED_SLASHES),
            'Queued',
            now(),
        ]);
        audit_log($this->db, 'workflows', 'queue', (int) $workflow['id'], $workflow['workflow_name'] . ' queued');
        return (int) $this->db->lastInsertId();
    }

    public function queueSnapshot(int $limit = 15): array {
        $stmt = $this->db->prepare('SELECT q.*, w.workflow_name FROM workflow_queue q LEFT JOIN workflows w ON w.id = q.workflow_id WHERE q.company_id = ? ORDER BY q.created_at DESC LIMIT ' . (int) $limit);
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function processQueue(int $limit = 25): array {
        $stmt = $this->db->prepare("SELECT * FROM workflow_queue WHERE company_id = ? AND queue_status = 'Queued' AND available_at <= NOW() ORDER BY created_at ASC LIMIT " . (int) $limit);
        $stmt->execute([current_company_id()]);
        $jobs = $stmt->fetchAll();
        $results = [];
        foreach ($jobs as $job) {
            $results[] = $this->processJob($job);
        }
        return $results;
    }

    private function processJob(array $job): array {
        $workflow = (new Workflow($this->db))->get((int) $job['workflow_id']);
        $payload = json_decode((string) $job['payload_json'], true) ?: [];
        if (!$workflow) {
            $this->markJob((int) $job['id'], 'Failed', 'Workflow no longer exists');
            return ['status' => 'Failed', 'error' => 'Missing workflow'];
        }

        if (!$this->conditionMatches($workflow, $payload)) {
            $this->markJob((int) $job['id'], 'Skipped', 'Condition did not match');
            (new WorkflowRun($this->db))->create([
                'workflow_id' => $workflow['id'],
                'workflow_name' => $workflow['workflow_name'],
                'trigger_key' => $workflow['trigger_key'],
                'action_key' => $workflow['action_key'],
                'run_status' => 'Skipped',
                'details' => 'Condition did not match.',
            ]);
            return ['status' => 'Skipped'];
        }

        $result = $this->executeAction($workflow, $payload);
        $status = !empty($result['success']) ? 'Success' : 'Failed';
        $details = $result['message'] ?? ($status === 'Success' ? 'Workflow completed.' : 'Workflow failed.');
        $this->markJob((int) $job['id'], $status, $status === 'Success' ? null : $details);
        (new WorkflowRun($this->db))->create([
            'workflow_id' => $workflow['id'],
            'workflow_name' => $workflow['workflow_name'],
            'trigger_key' => $workflow['trigger_key'],
            'action_key' => $workflow['action_key'],
            'run_status' => $status,
            'details' => $details,
        ]);
        audit_log($this->db, 'workflows', strtolower($status), (int) $workflow['id'], $workflow['workflow_name'] . ': ' . $details);
        return ['status' => $status, 'details' => $details];
    }

    private function markJob(int $id, string $status, ?string $error = null): void {
        $stmt = $this->db->prepare('UPDATE workflow_queue SET queue_status = ?, processed_at = NOW(), error_text = ?, updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([$status, $error, $id, current_company_id()]);
    }

    private function conditionMatches(array $workflow, array $payload): bool {
        $field = trim((string) ($workflow['condition_field'] ?? ''));
        if ($field === '') {
            return true;
        }
        $operator = strtolower((string) ($workflow['condition_operator'] ?? 'equals'));
        $expected = (string) ($workflow['condition_value'] ?? '');
        $actual = array_get($payload, 'record.' . $field, array_get($payload, $field, ''));
        $actualString = is_scalar($actual) ? (string) $actual : '';

        return match ($operator) {
            'not_equals' => $actualString !== $expected,
            'contains' => str_contains(strtolower($actualString), strtolower($expected)),
            'greater_than' => (float) $actualString > (float) $expected,
            'less_than' => (float) $actualString < (float) $expected,
            default => $actualString === $expected,
        };
    }

    private function executeAction(array $workflow, array $payload): array {
        $action = $workflow['action_key'];
        $actionPayload = json_decode((string) ($workflow['action_payload'] ?? '{}'), true) ?: [];
        $record = $payload['record'] ?? [];

        return match ($action) {
            'send_email' => $this->sendEmailAction($actionPayload, $record),
            'send_sms' => $this->sendSmsAction($actionPayload, $record),
            'create_task' => $this->createTaskAction($actionPayload, $record),
            'notify_user' => $this->notifyUserAction($actionPayload, $record),
            'score_lead' => $this->scoreLeadAction($record),
            default => ['success' => false, 'message' => 'Unsupported action: ' . $action],
        };
    }

    private function sendEmailAction(array $actionPayload, array $record): array {
        $recipient = $record['email'] ?? ($actionPayload['recipient'] ?? '');
        if (!$recipient) {
            return ['success' => false, 'message' => 'No email recipient available'];
        }
        $service = new CommunicationService($this->db);
        $service->queue([
            'channel' => 'Email',
            'recipient' => $recipient,
            'subject_line' => render_tokens((string) ($actionPayload['subject'] ?? 'CRM Follow-up'), ['record' => $record]),
            'body_text' => render_tokens((string) ($actionPayload['body'] ?? 'Hello {{record.lead_name}}, thank you for your interest.'), ['record' => $record]),
            'template_id' => !empty($actionPayload['template_id']) ? (int) $actionPayload['template_id'] : null,
            'related_type' => $record['type'] ?? null,
            'related_id' => $record['id'] ?? null,
            'context' => ['record' => $record],
        ], false);
        return ['success' => true, 'message' => 'Email queued for ' . $recipient];
    }

    private function sendSmsAction(array $actionPayload, array $record): array {
        $recipient = $record['phone'] ?? ($actionPayload['recipient'] ?? '');
        if (!$recipient) {
            return ['success' => false, 'message' => 'No SMS recipient available'];
        }
        $service = new CommunicationService($this->db);
        $service->queue([
            'channel' => 'SMS',
            'recipient' => $recipient,
            'body_text' => render_tokens((string) ($actionPayload['body'] ?? 'Reminder from CS One CRM.'), ['record' => $record]),
            'template_id' => !empty($actionPayload['template_id']) ? (int) $actionPayload['template_id'] : null,
            'related_type' => $record['type'] ?? null,
            'related_id' => $record['id'] ?? null,
            'context' => ['record' => $record],
        ], false);
        return ['success' => true, 'message' => 'SMS queued for ' . $recipient];
    }

    private function createTaskAction(array $actionPayload, array $record): array {
        $assignedUserId = !empty($actionPayload['assigned_user_id']) ? (int) $actionPayload['assigned_user_id'] : (!empty($record['assigned_user_id']) ? (int) $record['assigned_user_id'] : current_user_id());
        $taskId = (new Task($this->db))->create([
            'task_name' => render_tokens((string) ($actionPayload['task_name'] ?? 'Workflow follow-up task'), ['record' => $record]),
            'related_type' => $record['type'] ?? ($actionPayload['related_type'] ?? 'Workflow'),
            'related_name' => $record['company_name'] ?? $record['deal_name'] ?? $record['lead_name'] ?? ($actionPayload['related_name'] ?? 'Workflow'),
            'assigned_user_id' => $assignedUserId,
            'priority_level' => $actionPayload['priority'] ?? 'Normal',
            'due_date' => $actionPayload['due_date'] ?? date('Y-m-d', strtotime('+2 days')),
            'status' => 'Open',
            'notes' => render_tokens((string) ($actionPayload['notes'] ?? 'Created by workflow automation.'), ['record' => $record]),
        ]);
        return ['success' => true, 'message' => 'Task #' . $taskId . ' created'];
    }

    private function notifyUserAction(array $actionPayload, array $record): array {
        $userId = !empty($actionPayload['user_id']) ? (int) $actionPayload['user_id'] : (!empty($record['assigned_user_id']) ? (int) $record['assigned_user_id'] : null);
        (new Notification($this->db))->create([
            'user_id' => $userId,
            'title' => render_tokens((string) ($actionPayload['title'] ?? 'Workflow notification'), ['record' => $record]),
            'message_text' => render_tokens((string) ($actionPayload['message'] ?? 'A workflow event requires your attention.'), ['record' => $record]),
            'level_name' => $actionPayload['level_name'] ?? 'info',
            'link_url' => $actionPayload['link_url'] ?? 'index.php?page=workflows',
        ]);
        return ['success' => true, 'message' => 'Notification created'];
    }

    private function scoreLeadAction(array $record): array {
        if (empty($record['id'])) {
            return ['success' => false, 'message' => 'Lead id missing for scoring'];
        }
        $result = (new AiService($this->db))->scoreLead((int) $record['id']);
        return ['success' => true, 'message' => $result['explanation'] ?? 'Lead scored'];
    }
}
