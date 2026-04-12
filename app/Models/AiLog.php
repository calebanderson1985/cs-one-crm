<?php
namespace App\Models;

class AiLog extends BaseModel {
    public function list(int $limit = 25): array {
        $stmt = $this->db->prepare('SELECT * FROM ai_logs WHERE company_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit);
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }
    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO ai_logs (company_id, user_id, tool_name, input_text, output_text, created_at) VALUES (?,?,?,?,?,NOW())');
        $stmt->execute([current_company_id(), $data['user_id'] ?? current_user_id() ?: null, $data['tool_name'], $data['input_text'] ?: null, $data['output_text'] ?: null]);
        return (int) $this->db->lastInsertId();
    }
}
