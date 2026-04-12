<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Announcement;
use App\Models\Notification;

class AnnouncementController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('announcements', 'view');
        $model = new Announcement($this->db);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('announcements', 'create');
                $id = $model->create($_POST);
                $this->broadcast($id, $_POST);
                audit_log($this->db, 'announcements', 'create', $id, 'Announcement created and broadcast');
                flash('success', 'Announcement created.');
            } elseif ($action === 'toggle') {
                Auth::requirePermission('announcements', 'edit');
                $id = (int)($_POST['id'] ?? 0);
                $model->toggle($id);
                audit_log($this->db, 'announcements', 'toggle', $id, 'Announcement status toggled');
                flash('success', 'Announcement status updated.');
            } elseif ($action === 'delete') {
                Auth::requirePermission('announcements', 'delete');
                $id = (int)($_POST['id'] ?? 0);
                $model->delete($id);
                audit_log($this->db, 'announcements', 'delete', $id, 'Announcement deleted');
                flash('success', 'Announcement deleted.');
            }
            redirect('index.php?page=announcements');
        }

        $announcements = $model->list();
        View::render('admin/announcements', compact('announcements'));
    }

    private function broadcast(int $announcementId, array $data): void {
        $scope = (string)($data['audience_scope'] ?? 'company');
        $stmt = $this->db->prepare('SELECT id, role FROM users WHERE company_id = ? ORDER BY id ASC');
        $stmt->execute([current_company_id()]);
        $users = $stmt->fetchAll();
        $notification = new Notification($this->db);
        foreach ($users as $user) {
            $role = (string)($user['role'] ?? '');
            if ($scope === 'admins' && $role !== 'admin') continue;
            if ($scope === 'managers' && !in_array($role, ['admin','manager'], true)) continue;
            if ($scope === 'agents' && !in_array($role, ['admin','manager','agent'], true)) continue;
            $notification->create([
                'user_id' => (int)$user['id'],
                'notification_type' => 'Announcement',
                'title_text' => trim((string)($data['title'] ?? 'Announcement')),
                'body_text' => trim((string)($data['body_text'] ?? '')),
                'is_read' => 0,
                'related_type' => 'Announcement',
                'related_id' => $announcementId,
            ]);
        }
    }
}
