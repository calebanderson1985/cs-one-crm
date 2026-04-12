<?php
namespace App\Models;

class ApiRequestLog extends BaseModel {
    public function listRecent(int $limit = 100): array {
        $stmt = $this->db->prepare("SELECT * FROM api_request_logs WHERE company_id = ? ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function summary(): array {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total_requests, SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS error_requests, MAX(created_at) AS last_request_at FROM api_request_logs WHERE company_id = ?");
        $stmt->execute([current_company_id()]);
        return $stmt->fetch() ?: ['total_requests'=>0,'error_requests'=>0,'last_request_at'=>null];
    }

    public function log(array $data): void {
        $stmt = $this->db->prepare('INSERT INTO api_request_logs (company_id, api_token_id, resource_name, http_method, status_code, request_path, ip_address, scope_text, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute([
            current_company_id(),
            $data['api_token_id'] ?? null,
            $data['resource_name'] ?? null,
            $data['http_method'] ?? null,
            $data['status_code'] ?? 200,
            $data['request_path'] ?? null,
            $data['ip_address'] ?? null,
            $data['scope_text'] ?? null,
        ]);
    }
}
