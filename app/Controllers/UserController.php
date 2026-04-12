<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;

class UserController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('users', 'view');
        $model = new User($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('users', 'create');
                $id = $model->create($_POST);
                audit_log($this->db, 'users', 'create', $id, 'User created');
                flash('success', 'User created.');
            }
            if ($action === 'update') {
                Auth::requirePermission('users', 'edit');
                $id = (int) $_POST['id'];
                $model->update($id, $_POST);
                audit_log($this->db, 'users', 'update', $id, 'User updated');
                flash('success', 'User updated.');
            }
            if ($action === 'delete') {
                Auth::requirePermission('users', 'delete');
                $id = (int) $_POST['id'];
                if ($id === current_user_id()) {
                    flash('error', 'You cannot delete your own account while signed in.');
                } else {
                    $model->delete($id);
                    audit_log($this->db, 'users', 'delete', $id, 'User deleted');
                    flash('success', 'User deleted.');
                }
            }
            redirect('index.php?page=users');
        }
        $users = $model->list();
        $editUser = request_id() ? $model->get(request_id()) : null;
        $managerOptions = $model->assigneeOptions();
        $clientOptions = $model->clientLinkOptions();
        View::render('admin/users', compact('users', 'editUser', 'managerOptions', 'clientOptions'));
    }
}
