<?php
namespace App\Models;

use App\Services\PermissionService;

class RolePermission extends BaseModel {
    public function matrix(): array {
        $stmt = $this->db->prepare('SELECT * FROM role_permissions WHERE company_id = ? ORDER BY role_name ASC, module_name ASC');
        $stmt->execute([current_company_id()]);
        $rows = $stmt->fetchAll();
        $matrix = [];
        foreach ($rows as $row) {
            $matrix[$row['role_name']][$row['module_name']] = [
                'view' => (int) $row['can_view'],
                'create' => (int) $row['can_create'],
                'edit' => (int) $row['can_edit'],
                'delete' => (int) $row['can_delete'],
            ];
        }
        foreach (PermissionService::roles() as $role) {
            foreach (PermissionService::modules() as $module) {
                $matrix[$role][$module] = $matrix[$role][$module] ?? [
                    'view' => PermissionService::defaultCapability($role, $module, 'view') ? 1 : 0,
                    'create' => PermissionService::defaultCapability($role, $module, 'create') ? 1 : 0,
                    'edit' => PermissionService::defaultCapability($role, $module, 'edit') ? 1 : 0,
                    'delete' => PermissionService::defaultCapability($role, $module, 'delete') ? 1 : 0,
                ];
            }
        }
        return $matrix;
    }

    public function save(array $posted): void {
        $stmt = $this->db->prepare('INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at) VALUES (?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete), updated_at=VALUES(updated_at)');
        foreach (PermissionService::roles() as $role) {
            foreach (PermissionService::modules() as $module) {
                $caps = [];
                foreach (PermissionService::capabilities() as $capability) {
                    $caps[$capability] = !empty($posted['permissions'][$role][$module][$capability]) ? 1 : 0;
                }
                $stmt->execute([current_company_id(), $role, $module, $caps['view'], $caps['create'], $caps['edit'], $caps['delete']]);
            }
        }
    }
}
