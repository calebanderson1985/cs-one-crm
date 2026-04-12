<?php
namespace App\Models;

class SupportTicket extends BaseModel {
    public function list(array $filters = []): array {
        [$where, $params] = $this->buildScope('support_tickets');
        $clauses = [];
        if ($where) { $clauses[] = $where; }
        if (!empty($filters['status'])) { $clauses[] = 'status_name = ?'; $params[] = $filters['status']; }
        if (!empty($filters['priority'])) { $clauses[] = 'priority_name = ?'; $params[] = $filters['priority']; }
        if (!empty($filters['q'])) { $clauses[] = '(title LIKE ? OR detail_text LIKE ?)'; $params[] = '%' . $filters['q'] . '%'; $params[] = '%' . $filters['q'] . '%'; }
        $sql = 'SELECT * FROM support_tickets' . ($clauses ? ' WHERE ' . implode(' AND ', $clauses) : '') . ' ORDER BY created_at DESC, id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO support_tickets (company_id, title, category_name, priority_name, status_name, owner_user_id, detail_text, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            current_company_id(),
            trim((string) ($data['title'] ?? '')),
            trim((string) ($data['category_name'] ?? 'General')),
            trim((string) ($data['priority_name'] ?? 'Normal')),
            trim((string) ($data['status_name'] ?? 'Open')),
            !empty($data['owner_user_id']) ? (int) $data['owner_user_id'] : null,
            trim((string) ($data['detail_text'] ?? '')),
            current_user_id() ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void {
        $sql = "UPDATE support_tickets SET status_name = ?, resolved_at = CASE WHEN ? IN ('Resolved','Closed') THEN NOW() ELSE NULL END, updated_at = NOW() WHERE id = ? AND company_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $status, $id, current_company_id()]);
    }

    public function delete(int $id): void {
        $this->deleteRecord('support_tickets', $id);
    }
}
