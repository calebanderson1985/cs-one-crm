<?php
namespace App\Services;

use PDO;

class PermissionService {
    public static function roles(): array {
        return ['admin', 'manager', 'agent', 'client'];
    }

    public static function modules(): array {
        return [
            'dashboard', 'clients', 'leads', 'deals', 'tasks', 'communications', 'documents',
            'commissions', 'reports', 'workflows', 'notifications', 'portals', 'users', 'audit',
            'features', 'settings', 'api', 'permissions', 'ai', 'tokens', 'onboarding', 'api_analytics', 'company_switch', 'knowledge_base', 'sla'
        ];
    }

    public static function capabilities(): array {
        return ['view', 'create', 'edit', 'delete'];
    }

    public static function defaultMatrix(): array {
        $all = array_fill_keys(self::modules(), ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1]);
        return [
            'admin' => $all,
            'manager' => [
                'dashboard' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'clients' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'leads' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'deals' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'tasks' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'communications' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'documents' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'commissions' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'reports' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'workflows' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'notifications' => ['view' => 1, 'create' => 0, 'edit' => 1, 'delete' => 0],
                'portals' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'users' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'audit' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'features' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'settings' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'api' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'permissions' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'ai' => ['view' => 1, 'create' => 1, 'edit' => 0, 'delete' => 0],
                'tokens' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'onboarding' => ['view' => 1, 'create' => 0, 'edit' => 1, 'delete' => 0],
                'api_analytics' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'company_switch' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'knowledge_base' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'sla' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
            ],
            'agent' => [
                'dashboard' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'clients' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 0],
                'leads' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 0],
                'deals' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 0],
                'tasks' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 0],
                'communications' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 0],
                'documents' => ['view' => 1, 'create' => 1, 'edit' => 0, 'delete' => 0],
                'commissions' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'reports' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'workflows' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'notifications' => ['view' => 1, 'create' => 0, 'edit' => 1, 'delete' => 0],
                'portals' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'users' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'audit' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'features' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'settings' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'api' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'permissions' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'ai' => ['view' => 1, 'create' => 1, 'edit' => 0, 'delete' => 0],
                'tokens' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'onboarding' => ['view' => 1, 'create' => 0, 'edit' => 1, 'delete' => 0],
                'api_analytics' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'company_switch' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'knowledge_base' => ['view' => 1, 'create' => 1, 'edit' => 1, 'delete' => 0],
                'sla' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
            ],
            'client' => [
                'dashboard' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'clients' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'leads' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'deals' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'tasks' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'communications' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'documents' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'commissions' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'reports' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'workflows' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'notifications' => ['view' => 1, 'create' => 0, 'edit' => 1, 'delete' => 0],
                'portals' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'users' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'audit' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'features' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'settings' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'api' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'permissions' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'ai' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'tokens' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'onboarding' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'api_analytics' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'company_switch' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'knowledge_base' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'sla' => ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0],
            ],
        ];
    }

    public static function defaultCapability(string $role, string $module, string $capability = 'view'): bool {
        $matrix = self::defaultMatrix();
        return !empty($matrix[$role][$module][$capability]);
    }

    public static function seedDefaults(PDO $db, int $companyId): void {
        $matrix = self::defaultMatrix();
        $stmt = $db->prepare('INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at) VALUES (?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete), updated_at=VALUES(updated_at)');
        foreach ($matrix as $role => $modules) {
            foreach (self::modules() as $module) {
                $caps = $modules[$module] ?? ['view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0];
                $stmt->execute([$companyId, $role, $module, (int) !empty($caps['view']), (int) !empty($caps['create']), (int) !empty($caps['edit']), (int) !empty($caps['delete'])]);
            }
        }
    }

    public static function allowed(PDO $db, string $role, string $module, string $capability = 'view', ?int $companyId = null): bool {
        $companyId = $companyId ?: current_company_id();
        $column = match ($capability) {
            'create' => 'can_create',
            'edit' => 'can_edit',
            'delete' => 'can_delete',
            default => 'can_view',
        };
        $stmt = $db->prepare("SELECT {$column} FROM role_permissions WHERE company_id = ? AND role_name = ? AND module_name = ? LIMIT 1");
        $stmt->execute([$companyId, $role, $module]);
        $value = $stmt->fetchColumn();
        if ($value === false) {
            return self::defaultCapability($role, $module, $capability);
        }
        return (bool) $value;
    }
}
