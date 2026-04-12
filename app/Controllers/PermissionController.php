<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\RolePermission;
use App\Services\PermissionService;

class PermissionController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('permissions', 'view');
        $model = new RolePermission($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            Auth::requirePermission('permissions', 'edit');
            $model->save($_POST);
            audit_log($this->db, 'permissions', 'update', null, 'Role permission matrix updated');
            flash('success', 'Permissions saved.');
            redirect('index.php?page=permissions');
        }
        $matrix = $model->matrix();
        $roles = PermissionService::roles();
        $modules = PermissionService::modules();
        $capabilities = PermissionService::capabilities();
        View::render('admin/permissions', compact('matrix', 'roles', 'modules', 'capabilities'));
    }
}
