<?php
namespace App\Models;

class WorkerHeartbeat extends BaseModel {
    public function upsert(string $workerName, array $payload = []): void {
        $stmt = $this->db->prepare('INSERT INTO worker_heartbeats (company_id, worker_name, heartbeat_at, status_text, payload_json) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE heartbeat_at = VALUES(heartbeat_at), status_text = VALUES(status_text), payload_json = VALUES(payload_json)');
        $stmt->execute([
            current_company_id(),
            $workerName,
            now(),
            $payload['status_text'] ?? 'ok',
            json_encode($payload, JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function listAll(): array {
        return $this->all('worker_heartbeats', 'heartbeat_at DESC, worker_name ASC');
    }
}
