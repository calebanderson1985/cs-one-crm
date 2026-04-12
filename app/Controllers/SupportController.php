<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Notification;
use App\Models\SupportTicket;
use App\Models\User;

class SupportController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('support', 'view');
        $model = new SupportTicket($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('support', 'create');
                $id = $model->create($_POST);
                (new Notification($this->db))->create([
                    'title' => 'New support ticket',
                    'message_text' => 'Ticket #' . $id . ' was created.',
                    'level_name' => 'info',
                    'link_url' => 'index.php?page=support',
                ]);
                audit_log($this->db, 'support', 'create', $id, 'Support ticket created');
                flash('success', 'Support ticket created.');
            } elseif ($action === 'status') {
                Auth::requirePermission('support', 'edit');
                $id = (int)($_POST['id'] ?? 0);
                $status = trim((string)($_POST['status_name'] ?? 'Open'));
                $model->updateStatus($id, $status);
                audit_log($this->db, 'support', 'status', $id, 'Support ticket moved to ' . $status);
                flash('success', 'Support ticket updated.');
            } elseif ($action === 'delete') {
                Auth::requirePermission('support', 'delete');
                $id = (int)($_POST['id'] ?? 0);
                $model->delete($id);
                audit_log($this->db, 'support', 'delete', $id, 'Support ticket deleted');
                flash('success', 'Support ticket deleted.');
            }
            redirect('index.php?page=support');
        }

        $filters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'priority' => trim((string)($_GET['priority'] ?? '')),
            'q' => trim((string)($_GET['q'] ?? '')),
        ];
        $tickets = $model->list($filters);
        $users = (new User($this->db))->list();
        View::render('admin/support', compact('tickets', 'filters', 'users'));
    }
}
