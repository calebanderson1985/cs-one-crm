<?php
namespace App\Models;

class Message extends BaseModel {
    public function list(): array { return $this->all('communications', 'created_at DESC'); }
    public function get(int $id): ?array { return $this->find('communications', $id); }
    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO communications (company_id, related_type, related_id, channel, direction, recipient, subject_line, body_text, status, provider_name, template_id, created_by, sent_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            current_company_id(),
            $data['related_type'] ?: null,
            !empty($data['related_id']) ? (int) $data['related_id'] : null,
            $data['channel'],
            $data['direction'],
            $data['recipient'],
            $data['subject_line'] ?: null,
            $data['body_text'] ?: null,
            $data['status'],
            $data['provider_name'] ?: null,
            !empty($data['template_id']) ? (int) $data['template_id'] : null,
            $data['created_by'] ?? current_user_id() ?: null,
            $data['sent_at'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }
    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare('UPDATE communications SET related_type=?, related_id=?, channel=?, direction=?, recipient=?, subject_line=?, body_text=?, status=?, provider_name=?, template_id=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([$data['related_type'] ?: null, !empty($data['related_id']) ? (int) $data['related_id'] : null, $data['channel'], $data['direction'], $data['recipient'], $data['subject_line'] ?: null, $data['body_text'] ?: null, $data['status'], $data['provider_name'] ?: null, !empty($data['template_id']) ? (int) $data['template_id'] : null, $id, current_company_id()]);
    }
    public function setStatus(int $id, string $status, ?string $provider = null, ?string $sentAt = null): void {
        $stmt = $this->db->prepare('UPDATE communications SET status = ?, provider_name = COALESCE(?, provider_name), sent_at = COALESCE(?, sent_at), updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([$status, $provider, $sentAt, $id, current_company_id()]);
    }
    public function delete(int $id): void { $this->deleteRecord('communications', $id); }
}
