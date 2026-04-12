<?php
namespace App\Models;

class SupportTicket extends BaseModel {
    public function list(array $filters = []): array {
        [$where, $params] = $this->buildScope('support_tickets');
        $clauses = [];
        if ($where) { $clauses[] = str_replace('support_tickets', 'st', $where); }
        if (!empty($filters['status'])) { $clauses[] = 'st.status_name = ?'; $params[] = $filters['status']; }
        if (!empty($filters['priority'])) { $clauses[] = 'st.priority_name = ?'; $params[] = $filters['priority']; }
        if (!empty($filters['q'])) { $clauses[] = '(st.title LIKE ? OR st.detail_text LIKE ?)'; $params[] = '%' . $filters['q'] . '%'; $params[] = '%' . $filters['q'] . '%'; }
        $sql = 'SELECT st.*, sp.policy_name AS sla_policy_name FROM support_tickets st LEFT JOIN sla_policies sp ON sp.id = st.sla_policy_id AND sp.company_id = st.company_id' . ($clauses ? ' WHERE ' . implode(' AND ', $clauses) : '') . ' ORDER BY st.created_at DESC, st.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO support_tickets (company_id, title, category_name, priority_name, status_name, owner_user_id, detail_text, created_by, sla_policy_id, response_due_at, resolution_due_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            current_company_id(),
            trim((string) ($data['title'] ?? '')),
            trim((string) ($data['category_name'] ?? 'General')),
            trim((string) ($data['priority_name'] ?? 'Normal')),
            trim((string) ($data['status_name'] ?? 'Open')),
            !empty($data['owner_user_id']) ? (int) $data['owner_user_id'] : null,
            trim((string) ($data['detail_text'] ?? '')),
            current_user_id() ?: null,
            !empty($data['sla_policy_id']) ? (int) $data['sla_policy_id'] : null,
            $this->calculateDueAt((int)($data['sla_policy_id'] ?? 0), 'response'),
            $this->calculateDueAt((int)($data['sla_policy_id'] ?? 0), 'resolution'),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void {
        $sql = "UPDATE support_tickets SET status_name = ?, resolved_at = CASE WHEN ? IN ('Resolved','Closed') THEN NOW() ELSE NULL END, updated_at = NOW() WHERE id = ? AND company_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $status, $id, current_company_id()]);
    }


    private function calculateDueAt(int $slaPolicyId, string $type): ?string {
        if ($slaPolicyId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT response_minutes, resolution_minutes FROM sla_policies WHERE id = ? AND company_id = ? LIMIT 1');
        $stmt->execute([$slaPolicyId, current_company_id()]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $minutes = $type === 'response' ? (int)($row['response_minutes'] ?? 0) : (int)($row['resolution_minutes'] ?? 0);
        if ($minutes <= 0) {
            return null;
        }
        return date('Y-m-d H:i:s', time() + ($minutes * 60));
    }

    public function slaSummary(): array {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN resolution_due_at IS NOT NULL AND resolution_due_at < NOW() AND status_name NOT IN ('Resolved','Closed') THEN 1 ELSE 0 END) AS breached, SUM(CASE WHEN resolution_due_at IS NOT NULL AND resolution_due_at >= NOW() AND status_name NOT IN ('Resolved','Closed') THEN 1 ELSE 0 END) AS active FROM support_tickets WHERE company_id = ?");
        $stmt->execute([current_company_id()]);
        return $stmt->fetch() ?: ['total' => 0, 'breached' => 0, 'active' => 0];
    }

    public function delete(int $id): void {
        $this->deleteRecord('support_tickets', $id);
    }
}
