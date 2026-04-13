<?php
namespace App\Models;

class SupportEmailIngestion extends BaseModel {
    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO support_email_ingestions (company_id, ticket_id, from_email, subject_line, source_message_id, status_name, notes_text, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
        $stmt->execute([
            $data['company_id'] ?? current_company_id(),
            !empty($data['ticket_id']) ? (int)$data['ticket_id'] : null,
            trim((string)($data['from_email'] ?? '')),
            trim((string)($data['subject_line'] ?? '')),
            trim((string)($data['source_message_id'] ?? '')) ?: null,
            trim((string)($data['status_name'] ?? 'Processed')),
            $data['notes_text'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }
}
