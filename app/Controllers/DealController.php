<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Deal;
use App\Models\Notification;
use App\Models\User;
use App\Services\WorkflowEngine;

class DealController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('deals', 'view');
        $model = new Deal($this->db);
        $userModel = new User($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('deals', 'create');
                $id = $model->create($_POST);
                $record = $model->get($id);
                $this->notifyOwner($record);
                (new WorkflowEngine($this->db))->onEvent('deal.created', ['record' => ($record ?: []) + ['type' => 'Deal']]);
                audit_log($this->db, 'deals', 'create', $id, 'Deal created');
                flash('success', 'Deal created.');
            }
            if ($action === 'update') {
                Auth::requirePermission('deals', 'edit');
                $id = (int) $_POST['id'];
                $model->update($id, $_POST);
                $record = $model->get($id);
                $this->notifyOwner($record, 'Deal updated');
                (new WorkflowEngine($this->db))->onEvent('deal.updated', ['record' => ($record ?: []) + ['type' => 'Deal']]);
                audit_log($this->db, 'deals', 'update', $id, 'Deal updated');
                flash('success', 'Deal updated.');
            }
            if ($action === 'delete') {
                Auth::requirePermission('deals', 'delete');
                $id = (int) $_POST['id'];
                $model->delete($id);
                audit_log($this->db, 'deals', 'delete', $id, 'Deal deleted');
                flash('success', 'Deal deleted.');
            }
            redirect('index.php?page=deals');
        }
        $deals = $model->list();
        $editDeal = request_id() ? $model->get(request_id()) : null;
        $assignableUsers = $userModel->assigneeOptions();
        View::render('deals/index', compact('deals', 'editDeal', 'assignableUsers'));
    }

    private function notifyOwner(?array $record, string $title = 'Deal assigned'): void {
        if (!$record || empty($record['owner_user_id'])) {
            return;
        }
        (new Notification($this->db))->create([
            'user_id' => (int) $record['owner_user_id'],
            'title' => $title,
            'message_text' => ($record['deal_name'] ?? 'Deal') . ' is assigned to you.',
            'level_name' => 'info',
            'link_url' => 'index.php?page=deals&id=' . (int) $record['id'],
        ]);
    }
}
