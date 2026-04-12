<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Notification;

class NotificationController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('notifications', 'view');
        $model = new Notification($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'mark_read';
            if ($action === 'mark_read') {
                $id = (int) ($_POST['id'] ?? 0);
                $model->markRead($id);
                audit_log($this->db, 'notifications', 'mark_read', $id, 'Notification marked read');
                flash('success', 'Notification marked as read.');
            }
            if ($action === 'mark_all_read') {
                $model->markAllRead();
                audit_log($this->db, 'notifications', 'mark_all_read', null, 'All notifications marked read');
                flash('success', 'All notifications marked as read.');
            }
            redirect('index.php?page=notifications');
        }
        $notifications = $model->list();
        View::render('notifications/index', compact('notifications'));
    }
}
