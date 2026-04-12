<?php
namespace App\Models;

class AuditLog extends BaseModel {
    public function list(array $filters = []): array {
        [$where, $params] = $this->buildScope('audit_logs');
        $clauses = [];
        if ($where) { $clauses[] = $where; }
        if (!empty($filters['module'])) { $clauses[] = 'module_name = ?'; $params[] = $filters['module']; }
        if (!empty($filters['action'])) { $clauses[] = 'action_name = ?'; $params[] = $filters['action']; }
        if (!empty($filters['user_id'])) { $clauses[] = 'user_id = ?'; $params[] = (int)$filters['user_id']; }
        if (!empty($filters['date_from'])) { $clauses[] = 'created_at >= ?'; $params[] = $filters['date_from'] . ' 00:00:00'; }
        if (!empty($filters['date_to'])) { $clauses[] = 'created_at <= ?'; $params[] = $filters['date_to'] . ' 23:59:59'; }
        if (!empty($filters['q'])) { $clauses[] = '(summary_text LIKE ? OR ip_address LIKE ?)'; $params[] = '%' . $filters['q'] . '%'; $params[] = '%' . $filters['q'] . '%'; }
        $sql = 'SELECT * FROM audit_logs' . ($clauses ? ' WHERE ' . implode(' AND ', $clauses) : '') . ' ORDER BY created_at DESC, id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
