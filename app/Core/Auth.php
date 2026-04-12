<?php
namespace App\Core;

use App\Services\PermissionService;

class Auth {
    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public static function attempt(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'company_id' => $user['company_id'] ?? null,
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'manager_user_id' => $user['manager_user_id'] ?? null,
            'portal_client_id' => $user['portal_client_id'] ?? null,
        ];
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function requireLogin(): void {
        if (!self::user()) {
            redirect('index.php?page=login');
        }
    }

    public static function requireRole(array|string $roles): void {
        self::requireLogin();
        $roles = (array) $roles;
        $user = self::user();
        if (!$user || !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    public static function can(string $module, string $capability = 'view'): bool {
        $user = self::user();
        if (!$user) {
            return false;
        }
        global $pdo;
        if (!$pdo instanceof \PDO) {
            return PermissionService::defaultCapability($user['role'], $module, $capability);
        }
        return PermissionService::allowed($pdo, $user['role'], $module, $capability, (int) ($user['company_id'] ?? 1));
    }

    public static function canAccess(string $area): bool {
        return self::can($area, 'view');
    }

    public static function requirePermission(string $module, string $capability = 'view'): void {
        self::requireLogin();
        if (!self::can($module, $capability)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}
