<?php
namespace App\Models;

class SlaPolicy extends BaseModel {
    public function list(array $filters = []): array {
        [$where, $params] = $this->buildScope('sla_policies');
        $clauses = [];
        if ($where) { $clauses[] = $where; }
        if (!empty($filters['q'])) { $clauses[] = '(policy_name LIKE ? OR target_scope LIKE ?)'; $params[] = '%' . $filters['q'] . '%'; $params[] = '%' . $filters['q'] . '%'; }
        $sql = 'SELECT * FROM sla_policies' . ($clauses ? ' WHERE ' . implode(' AND ', $clauses) : '') . ' ORDER BY is_active DESC, policy_name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listActive(): array {
        $stmt = $this->db->prepare('SELECT * FROM sla_policies WHERE company_id = ? AND is_active = 1 ORDER BY policy_name ASC');
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO sla_policies (company_id, policy_name, target_scope, response_minutes, resolution_minutes, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([current_company_id(), trim((string)($data['policy_name'] ?? '')), trim((string)($data['target_scope'] ?? 'General')), (int)($data['response_minutes'] ?? 60), (int)($data['resolution_minutes'] ?? 480), !empty($data['is_active']) ? 1 : 0]);
        return (int) $this->db->lastInsertId();
    }

    public function updateRecord(int $id, array $data): void {
        $stmt = $this->db->prepare('UPDATE sla_policies SET policy_name = ?, target_scope = ?, response_minutes = ?, resolution_minutes = ?, is_active = ?, updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([trim((string)($data['policy_name'] ?? '')), trim((string)($data['target_scope'] ?? 'General')), (int)($data['response_minutes'] ?? 60), (int)($data['resolution_minutes'] ?? 480), !empty($data['is_active']) ? 1 : 0, $id, current_company_id()]);
    }

    public function delete(int $id): void {
        $this->deleteRecord('sla_policies', $id);
    }
}
