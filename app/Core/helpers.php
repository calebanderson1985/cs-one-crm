<?php

function e(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function money(float|int|string|null $value): string {
    return '$' . number_format((float) $value, 2);
}

function active_nav(string $page, array|string $targets): string {
    return in_array($page, (array) $targets, true) ? 'is-active' : '';
}

function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function request_id(): int {
    return (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
}

function now(): string {
    return date('Y-m-d H:i:s');
}

function csrf_token(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $token = $_POST['_csrf'] ?? '';
    if (!$token || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid CSRF token');
    }
}

function current_company_id(): int {
    if (!empty($_SESSION['user']['company_id'])) {
        return (int) $_SESSION['user']['company_id'];
    }
    if (!empty($_SERVER['HTTP_X_COMPANY_ID'])) {
        return (int) $_SERVER['HTTP_X_COMPANY_ID'];
    }
    if (!empty($_GET['company_id'])) {
        return (int) $_GET['company_id'];
    }
    return 1;
}

function current_user_id(): int {
    return (int) ($_SESSION['user']['id'] ?? 0);
}

function current_user_role(): string {
    return (string) ($_SESSION['user']['role'] ?? '');
}

function current_user_name(): string {
    return (string) ($_SESSION['user']['full_name'] ?? '');
}

function current_user_email(): string {
    return (string) ($_SESSION['user']['email'] ?? '');
}

function current_portal_client_id(): int {
    return (int) ($_SESSION['user']['portal_client_id'] ?? 0);
}

function is_json_request(): bool {
    return (isset($_GET['format']) && $_GET['format'] === 'json') || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function setting(PDO $db, string $key, ?string $default = null): ?string {
    $stmt = $db->prepare('SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = ? LIMIT 1');
    $stmt->execute([current_company_id(), $key]);
    $value = $stmt->fetchColumn();
    return $value !== false ? (string) $value : $default;
}

function parse_json_input(): array {
    $body = file_get_contents('php://input');
    if (!$body) {
        return [];
    }
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : [];
}

function array_get(array $source, string $key, mixed $default = null): mixed {
    $segments = explode('.', $key);
    $value = $source;
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function team_user_ids(PDO $db, ?array $user = null): array {
    $user = $user ?? ($_SESSION['user'] ?? null);
    if (!$user) {
        return [];
    }
    $companyId = (int) ($user['company_id'] ?? current_company_id());
    $role = (string) ($user['role'] ?? current_user_role());
    $userId = (int) ($user['id'] ?? current_user_id());

    if ($role === 'admin') {
        $stmt = $db->prepare('SELECT id FROM users WHERE company_id = ?');
        $stmt->execute([$companyId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    if ($role === 'manager') {
        $stmt = $db->prepare('SELECT id FROM users WHERE company_id = ? AND (id = ? OR manager_user_id = ?)');
        $stmt->execute([$companyId, $userId, $userId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    return [$userId];
}

function sql_in_clause(array $values): array {
    $values = array_values(array_filter(array_map('intval', $values), fn ($value) => $value > 0));
    if (!$values) {
        return ['(NULL)', []];
    }
    return ['(' . implode(',', array_fill(0, count($values), '?')) . ')', $values];
}

function render_tokens(string $text, array $context = []): string {
    return preg_replace_callback('/{{\s*([^}]+)\s*}}/', function (array $matches) use ($context) {
        $value = array_get($context, trim($matches[1]), '');
        if (is_array($value)) {
            return '';
        }
        return (string) $value;
    }, $text);
}

function audit_log(PDO $db, string $module, string $action, ?int $recordId = null, ?string $summary = null): void {
    if (!$db) {
        return;
    }
    $stmt = $db->prepare('INSERT INTO audit_logs (company_id, user_id, module_name, action_name, record_id, summary_text, ip_address, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
        current_company_id(),
        current_user_id() ?: null,
        $module,
        $action,
        $recordId,
        $summary,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
