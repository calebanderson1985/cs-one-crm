<?php
namespace App\Models;

class Announcement extends BaseModel {
    public function list(): array {
        return $this->all('announcements', 'is_active DESC, created_at DESC, id DESC');
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO announcements (company_id, title, body_text, audience_scope, is_active, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            current_company_id(),
            trim((string)($data['title'] ?? '')),
            trim((string)($data['body_text'] ?? '')),
            $data['audience_scope'] ?? 'company',
            !empty($data['is_active']) ? 1 : 0,
            current_user_id() ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function toggle(int $id): void {
        $stmt = $this->db->prepare('UPDATE announcements SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END, updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([$id, current_company_id()]);
    }

    public function delete(int $id): void {
        $this->deleteRecord('announcements', $id);
    }
}
