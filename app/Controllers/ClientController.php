<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Client;
use App\Models\Notification;
use App\Models\User;
use App\Services\WorkflowEngine;

class ClientController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('clients', 'view');
        $model = new Client($this->db);
        $userModel = new User($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('clients', 'create');
                $id = $model->create($_POST);
                $record = $model->get($id);
                $this->notifyAssignee($record);
                (new WorkflowEngine($this->db))->onEvent('client.created', ['record' => ($record ?: []) + ['type' => 'Client']]);
                audit_log($this->db, 'clients', 'create', $id, 'Client created');
                flash('success', 'Client created.');
            }
            if ($action === 'update') {
                Auth::requirePermission('clients', 'edit');
                $id = (int) $_POST['id'];
                $model->update($id, $_POST);
                $record = $model->get($id);
                $this->notifyAssignee($record, 'Client updated');
                (new WorkflowEngine($this->db))->onEvent('client.updated', ['record' => ($record ?: []) + ['type' => 'Client']]);
                audit_log($this->db, 'clients', 'update', $id, 'Client updated');
                flash('success', 'Client updated.');
            }
            if ($action === 'delete') {
                Auth::requirePermission('clients', 'delete');
                $id = (int) $_POST['id'];
                $model->delete($id);
                audit_log($this->db, 'clients', 'delete', $id, 'Client deleted');
                flash('success', 'Client deleted.');
            }
            redirect('index.php?page=clients');
        }
        $clients = $model->list();
        $editClient = request_id() ? $model->get(request_id()) : null;
        $assignableUsers = $userModel->assigneeOptions();
        View::render('clients/index', compact('clients', 'editClient', 'assignableUsers'));
    }

    private function notifyAssignee(?array $record, string $title = 'Client assigned'): void {
        if (!$record || empty($record['assigned_user_id'])) {
            return;
        }
        (new Notification($this->db))->create([
            'user_id' => (int) $record['assigned_user_id'],
            'title' => $title,
            'message_text' => $record['company_name'] . ' is assigned to you.',
            'level_name' => 'info',
            'link_url' => 'index.php?page=clients&id=' . (int) $record['id'],
        ]);
    }
}
