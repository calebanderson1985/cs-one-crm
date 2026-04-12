<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\User;
use App\Services\AiService;
use App\Services\WorkflowEngine;

class LeadController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('leads', 'view');
        $model = new Lead($this->db);
        $userModel = new User($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('leads', 'create');
                $id = $model->create($_POST);
                $scoreResult = (new AiService($this->db))->scoreLead($id);
                $record = $model->get($id);
                $this->notifyAssignee($record);
                (new WorkflowEngine($this->db))->onEvent('lead.created', ['record' => ($record ?: []) + ['type' => 'Lead']]);
                audit_log($this->db, 'leads', 'create', $id, $scoreResult['explanation'] ?? 'Lead created');
                flash('success', 'Lead created and scored at ' . (int) ($scoreResult['score'] ?? 0) . '.');
            }
            if ($action === 'update') {
                Auth::requirePermission('leads', 'edit');
                $id = (int) $_POST['id'];
                $model->update($id, $_POST);
                $scoreResult = (new AiService($this->db))->scoreLead($id);
                $record = $model->get($id);
                $this->notifyAssignee($record, 'Lead updated');
                (new WorkflowEngine($this->db))->onEvent('lead.updated', ['record' => ($record ?: []) + ['type' => 'Lead']]);
                audit_log($this->db, 'leads', 'update', $id, $scoreResult['explanation'] ?? 'Lead updated');
                flash('success', 'Lead updated.');
            }
            if ($action === 'delete') {
                Auth::requirePermission('leads', 'delete');
                $id = (int) $_POST['id'];
                $model->delete($id);
                audit_log($this->db, 'leads', 'delete', $id, 'Lead deleted');
                flash('success', 'Lead deleted.');
            }
            if ($action === 'convert') {
                Auth::requirePermission('leads', 'edit');
                $leadId = (int) $_POST['id'];
                $clientId = $model->convertToClient($leadId);
                $record = $model->get($leadId);
                (new WorkflowEngine($this->db))->onEvent('lead.converted', ['record' => ($record ?: []) + ['type' => 'Lead', 'client_id' => $clientId]]);
                audit_log($this->db, 'leads', 'convert', $leadId, 'Lead converted to client #' . (int) $clientId);
                flash('success', $clientId ? 'Lead converted to client.' : 'Lead not found.');
            }
            redirect('index.php?page=leads');
        }
        $leads = $model->list();
        $editLead = request_id() ? $model->get(request_id()) : null;
        $assignableUsers = $userModel->assigneeOptions();
        View::render('leads/index', compact('leads', 'editLead', 'assignableUsers'));
    }

    private function notifyAssignee(?array $record, string $title = 'Lead assigned'): void {
        if (!$record || empty($record['assigned_user_id'])) {
            return;
        }
        (new Notification($this->db))->create([
            'user_id' => (int) $record['assigned_user_id'],
            'title' => $title,
            'message_text' => ($record['lead_name'] ?? 'Lead') . ' is assigned to you.',
            'level_name' => 'info',
            'link_url' => 'index.php?page=leads&id=' . (int) $record['id'],
        ]);
    }
}
