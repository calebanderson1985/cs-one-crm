<?php
namespace App\Models;

class Notification extends BaseModel {
    public function list(): array {
        return $this->all('notifications', 'is_read ASC, created_at DESC');
    }

    public function unreadCount(): int {
        [$where, $params] = $this->buildScope('notifications');
        $clauses = array_filter([$where, 'is_read = 0']);
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE ' . implode(' AND ', $clauses));
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO notifications (company_id, user_id, title, message_text, level_name, link_url, is_read, created_at) VALUES (?,?,?,?,?,?,0,NOW())');
        $stmt->execute([
            $data['company_id'] ?? current_company_id(),
            $data['user_id'] ?? null,
            $data['title'],
            $data['message_text'] ?? null,
            $data['level_name'] ?? 'info',
            $data['link_url'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function markRead(int $id): void {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND company_id = ? AND (user_id IS NULL OR user_id = ?)');
        $stmt->execute([$id, current_company_id(), current_user_id() ?: null]);
    }

    public function markAllRead(): void {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE company_id = ? AND (user_id IS NULL OR user_id = ?)');
        $stmt->execute([current_company_id(), current_user_id() ?: null]);
    }
}
