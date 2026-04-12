<?php
namespace App\Models;

class ApiToken extends BaseModel {
    public function list(): array {
        $stmt = $this->db->prepare('SELECT id, company_id, token_name, token_prefix, scope_text, last_used_at, revoked_at, expires_at, created_by, created_at FROM api_tokens WHERE company_id = ? ORDER BY created_at DESC');
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function createToken(array $data): array {
        $plain = bin2hex(random_bytes(24));
        $prefix = substr($plain, 0, 12);
        $scopeText = $this->normalizeScopeText($data['scope_text'] ?? 'clients:read,leads:read,deals:read,tasks:read,communications:read');
        $stmt = $this->db->prepare('INSERT INTO api_tokens (company_id, token_name, token_prefix, token_hash, scope_text, expires_at, last_used_at, revoked_at, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            current_company_id(),
            trim((string)($data['token_name'] ?? 'API Token')),
            $prefix,
            hash('sha256', $plain),
            $scopeText,
            !empty($data['expires_at']) ? $data['expires_at'] : null,
            null,
            null,
            current_user_id() ?: null,
        ]);
        return ['id' => (int)$this->db->lastInsertId(), 'plain_text' => $plain, 'prefix' => $prefix, 'scope_text' => $scopeText];
    }

    public function rotate(int $id): ?array {
        $token = $this->get($id);
        if (!$token) { return null; }
        $plain = bin2hex(random_bytes(24));
        $prefix = substr($plain, 0, 12);
        $stmt = $this->db->prepare('UPDATE api_tokens SET token_prefix = ?, token_hash = ?, revoked_at = NULL, updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([$prefix, hash('sha256', $plain), $id, current_company_id()]);
        return ['id' => $id, 'plain_text' => $plain, 'prefix' => $prefix, 'scope_text' => $token['scope_text'] ?? ''];
    }

    public function revoke(int $id): void {
        $stmt = $this->db->prepare('UPDATE api_tokens SET revoked_at = NOW(), updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([$id, current_company_id()]);
    }

    public function get(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM api_tokens WHERE id = ? AND company_id = ? LIMIT 1');
        $stmt->execute([$id, current_company_id()]);
        return $stmt->fetch() ?: null;
    }

    public function findActiveByPlainText(string $plain): ?array {
        $hash = hash('sha256', $plain);
        $stmt = $this->db->prepare('SELECT * FROM api_tokens WHERE token_hash = ? AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at >= NOW()) LIMIT 1');
        $stmt->execute([$hash]);
        return $stmt->fetch() ?: null;
    }

    public function touchUsage(int $id): void {
        $stmt = $this->db->prepare('UPDATE api_tokens SET last_used_at = NOW(), updated_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function allows(array $token, string $resource, string $operation): bool {
        $scopeText = (string)($token['scope_text'] ?? '');
        $scopes = array_filter(array_map('trim', explode(',', $scopeText)));
        if (in_array('*', $scopes, true) || in_array($resource . ':*', $scopes, true)) {
            return true;
        }
        return in_array($resource . ':' . $operation, $scopes, true);
    }

    private function normalizeScopeText(string $scopeText): string {
        $scopes = array_unique(array_filter(array_map('trim', preg_split('/[\s,]+/', $scopeText))));
        return implode(',', $scopes);
    }
}
