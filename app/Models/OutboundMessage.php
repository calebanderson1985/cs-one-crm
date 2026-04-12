<?php
namespace App\Models;

class OutboundMessage extends BaseModel {
    public function list(): array {
        return $this->all('outbound_messages', 'created_at DESC');
    }
    public function get(int $id): ?array {
        return $this->find('outbound_messages', $id);
    }
    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO outbound_messages (company_id, communication_id, channel, recipient, subject_line, body_text, provider_name, send_status, attempt_count, provider_message_id, error_text, scheduled_at, sent_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([current_company_id(), $data['communication_id'] ?? null, $data['channel'], $data['recipient'], $data['subject_line'] ?: null, $data['body_text'] ?: null, $data['provider_name'] ?: null, $data['send_status'] ?? 'Queued', (int)($data['attempt_count'] ?? 0), $data['provider_message_id'] ?: null, $data['error_text'] ?: null, $data['scheduled_at'] ?? now(), $data['sent_at'] ?? null]);
        return (int) $this->db->lastInsertId();
    }
    public function pending(int $limit = 25): array {
        $stmt = $this->db->prepare("SELECT * FROM outbound_messages WHERE company_id = ? AND send_status IN ('Queued','Retry') AND scheduled_at <= NOW() ORDER BY created_at ASC LIMIT " . (int) $limit);
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }
    public function markSent(int $id, string $provider, ?string $providerMessageId = null): void {
        $stmt = $this->db->prepare("UPDATE outbound_messages SET send_status='Sent', provider_name=?, provider_message_id=?, sent_at=NOW(), updated_at=NOW(), error_text=NULL WHERE id=? AND company_id=?");
        $stmt->execute([$provider, $providerMessageId, $id, current_company_id()]);
    }
    public function markFailed(int $id, string $provider, string $error): void {
        $stmt = $this->db->prepare("UPDATE outbound_messages SET send_status='Failed', provider_name=?, error_text=?, attempt_count=attempt_count+1, updated_at=NOW() WHERE id=? AND company_id=?");
        $stmt->execute([$provider, $error, $id, current_company_id()]);
    }
}
