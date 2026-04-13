<?php
namespace App\Models;

class MailboxPollConfig extends BaseModel {
    public function list(): array {
        $stmt = $this->db->prepare('SELECT * FROM mailbox_poll_configs WHERE company_id = ? ORDER BY is_active DESC, config_name ASC');
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function createOrUpdate(array $data): int {
        $id = (int)($data['id'] ?? 0);
        $payload = [
            trim((string)($data['config_name'] ?? 'Primary Inbox')),
            trim((string)($data['host_name'] ?? '')),
            max(1, (int)($data['port_number'] ?? 993)),
            trim((string)($data['encryption_type'] ?? 'ssl')),
            trim((string)($data['username_text'] ?? '')),
            trim((string)($data['password_text'] ?? '')),
            trim((string)($data['inbox_name'] ?? 'INBOX')),
            trim((string)($data['sender_domain_filter'] ?? '')) ?: null,
            trim((string)($data['poll_mode'] ?? 'unseen')),
            !empty($data['is_active']) ? 1 : 0,
        ];
        if ($id > 0) {
            $stmt = $this->db->prepare('UPDATE mailbox_poll_configs SET config_name=?, host_name=?, port_number=?, encryption_type=?, username_text=?, password_text=?, inbox_name=?, sender_domain_filter=?, poll_mode=?, is_active=?, updated_at=NOW() WHERE id = ? AND company_id = ?');
            $stmt->execute(array_merge($payload, [$id, current_company_id()]));
            return $id;
        }
        $stmt = $this->db->prepare('INSERT INTO mailbox_poll_configs (company_id, config_name, host_name, port_number, encryption_type, username_text, password_text, inbox_name, sender_domain_filter, poll_mode, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute(array_merge([current_company_id()], $payload));
        return (int)$this->db->lastInsertId();
    }

    public function getActive(): array {
        $stmt = $this->db->prepare('SELECT * FROM mailbox_poll_configs WHERE company_id = ? AND is_active = 1 ORDER BY id ASC');
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function touchPolled(int $id): void {
        $stmt = $this->db->prepare('UPDATE mailbox_poll_configs SET last_polled_at = NOW(), updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([$id, current_company_id()]);
    }
}
