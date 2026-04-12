<?php
namespace App\Models;

class WebhookEvent extends BaseModel {
    public function logEvent(string $provider, string $eventType, string $payload, string $signatureHeader = '', bool $verified = false, string $status = 'received', ?string $response = null): int {
        $stmt = $this->db->prepare('INSERT INTO webhook_events (company_id, provider_name, event_type, payload_text, signature_header, is_verified, processing_status, response_text, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([current_company_id(), $provider, $eventType, $payload, $signatureHeader ?: null, $verified ? 1 : 0, $status, $response]);
        return (int) $this->db->lastInsertId();
    }

    public function list(): array {
        return $this->all('webhook_events', 'id DESC');
    }

    public function get(int $id): ?array {
        return $this->find('webhook_events', $id);
    }

    public function markProcessed(int $id, string $status, ?string $response = null): void {
        $stmt = $this->db->prepare('UPDATE webhook_events SET processing_status = ?, response_text = ?, processed_at = NOW(), updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([$status, $response, $id, current_company_id()]);
    }

    public function incrementReplay(int $id): void {
        $stmt = $this->db->prepare('UPDATE webhook_events SET replay_count = replay_count + 1, updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([$id, current_company_id()]);
    }
}
