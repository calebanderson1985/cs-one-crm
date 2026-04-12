<?php
namespace App\Models;

class WorkflowRun extends BaseModel {
    public function list(): array { return $this->all('workflow_runs', 'created_at DESC'); }
    public function recent(int $limit = 8): array {
        $limit = max(1, (int) $limit);
        $stmt = $this->db->prepare('SELECT * FROM workflow_runs WHERE company_id = ? ORDER BY created_at DESC LIMIT ' . $limit);
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }
    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO workflow_runs (company_id, workflow_id, workflow_name, trigger_key, action_key, run_status, details, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
        $stmt->execute([current_company_id(), $data['workflow_id'] ?? null, $data['workflow_name'], $data['trigger_key'], $data['action_key'], $data['run_status'], $data['details'] ?? null]);
        return (int) $this->db->lastInsertId();
    }
}
