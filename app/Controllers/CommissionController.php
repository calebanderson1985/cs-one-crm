<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Commission;
use App\Models\Notification;
use App\Models\User;

class CommissionController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('commissions', 'view');
        $model = new Commission($this->db);
        $userModel = new User($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('commissions', 'create');
                $id = $model->create($_POST);
                $record = $model->get($id);
                $this->notifyAgent($record);
                audit_log($this->db, 'commissions', 'create', $id, 'Commission created');
                flash('success', 'Commission recorded.');
            }
            if ($action === 'update') {
                Auth::requirePermission('commissions', 'edit');
                $id = (int) $_POST['id'];
                $model->update($id, $_POST);
                $record = $model->get($id);
                $this->notifyAgent($record, 'Commission updated');
                audit_log($this->db, 'commissions', 'update', $id, 'Commission updated');
                flash('success', 'Commission updated.');
            }
            if ($action === 'delete') {
                Auth::requirePermission('commissions', 'delete');
                $id = (int) $_POST['id'];
                $model->delete($id);
                audit_log($this->db, 'commissions', 'delete', $id, 'Commission deleted');
                flash('success', 'Commission deleted.');
            }
            redirect('index.php?page=commissions');
        }
        $commissions = $model->list();
        $editCommission = request_id() ? $model->get(request_id()) : null;
        $assignableUsers = $userModel->assigneeOptions();
        View::render('commissions/index', compact('commissions', 'editCommission', 'assignableUsers'));
    }

    private function notifyAgent(?array $record, string $title = 'Commission assigned'): void {
        if (!$record || empty($record['agent_user_id'])) {
            return;
        }
        (new Notification($this->db))->create([
            'user_id' => (int) $record['agent_user_id'],
            'title' => $title,
            'message_text' => 'Commission updated for ' . ($record['deal_name'] ?? 'deal') . '.',
            'level_name' => 'success',
            'link_url' => 'index.php?page=commissions&id=' . (int) $record['id'],
        ]);
    }
}
