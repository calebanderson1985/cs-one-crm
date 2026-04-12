<?php
namespace App\Models;

use PDO;

class QueueOps extends BaseModel {
    public function summary(): array {
        $companyId = current_company_id();
        $summary = [
            'workflow_queued' => 0,
            'workflow_failed' => 0,
            'outbound_queued' => 0,
            'outbound_failed' => 0,
        ];
        $stmt = $this->db->prepare("SELECT queue_status, COUNT(*) c FROM workflow_queue WHERE company_id = ? GROUP BY queue_status");
        $stmt->execute([$companyId]);
        foreach ($stmt->fetchAll() as $row) {
            if (($row['queue_status'] ?? '') === 'Queued') $summary['workflow_queued'] = (int)$row['c'];
            if (($row['queue_status'] ?? '') === 'Failed') $summary['workflow_failed'] = (int)$row['c'];
        }
        $stmt = $this->db->prepare("SELECT send_status, COUNT(*) c FROM outbound_messages WHERE company_id = ? GROUP BY send_status");
        $stmt->execute([$companyId]);
        foreach ($stmt->fetchAll() as $row) {
            if (in_array(($row['send_status'] ?? ''), ['Queued','Retry'], true)) $summary['outbound_queued'] += (int)$row['c'];
            if (($row['send_status'] ?? '') === 'Failed') $summary['outbound_failed'] = (int)$row['c'];
        }
        return $summary;
    }

    public function workflowFailed(): array {
        $stmt = $this->db->prepare("SELECT q.*, w.workflow_name FROM workflow_queue q LEFT JOIN workflows w ON w.id = q.workflow_id WHERE q.company_id = ? AND q.queue_status = 'Failed' ORDER BY q.updated_at DESC, q.id DESC LIMIT 100");
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function outboundFailed(): array {
        $stmt = $this->db->prepare("SELECT * FROM outbound_messages WHERE company_id = ? AND send_status = 'Failed' ORDER BY updated_at DESC, id DESC LIMIT 100");
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function retryWorkflow(int $id): void {
        $stmt = $this->db->prepare("UPDATE workflow_queue SET queue_status = 'Queued', error_text = NULL, processed_at = NULL, available_at = NOW(), updated_at = NOW() WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, current_company_id()]);
    }

    public function retryOutbound(int $id): void {
        $stmt = $this->db->prepare("UPDATE outbound_messages SET send_status = 'Retry', error_text = NULL, last_attempt_at = NULL, updated_at = NOW() WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, current_company_id()]);
    }
}
