<?php
namespace App\Models;

class MaintenanceRun extends BaseModel {
    public function listRecent(): array {
        return $this->all('maintenance_runs', 'created_at DESC, id DESC');
    }

    public function create(string $runType, array $result): int {
        $stmt = $this->db->prepare('INSERT INTO maintenance_runs (company_id, run_type, result_json, created_by, created_at) VALUES (?,?,?,?,NOW())');
        $stmt->execute([
            current_company_id(),
            $runType,
            json_encode($result, JSON_UNESCAPED_SLASHES),
            current_user_id() ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
