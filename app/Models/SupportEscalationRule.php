<?php
namespace App\Models;

class SupportEscalationRule extends BaseModel {
    public function list(array $filters = []): array {
        $clauses = ['company_id = ?'];
        $params = [current_company_id()];
        if (!empty($filters['q'])) {
            $clauses[] = '(rule_name LIKE ? OR priority_name LIKE ? OR category_name LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        $stmt = $this->db->prepare('SELECT * FROM support_escalation_rules WHERE ' . implode(' AND ', $clauses) . ' ORDER BY is_active DESC, sort_order ASC, id ASC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function activeRules(): array {
        $stmt = $this->db->prepare('SELECT * FROM support_escalation_rules WHERE company_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC');
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO support_escalation_rules (company_id, rule_name, priority_name, category_name, hours_after_breach, escalate_to_user_id, set_priority_name, set_status_name, comment_template, is_active, sort_order, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            current_company_id(),
            trim((string)($data['rule_name'] ?? '')),
            trim((string)($data['priority_name'] ?? '')),
            trim((string)($data['category_name'] ?? '')),
            max(0, (int)($data['hours_after_breach'] ?? 0)),
            !empty($data['escalate_to_user_id']) ? (int)$data['escalate_to_user_id'] : null,
            trim((string)($data['set_priority_name'] ?? '')),
            trim((string)($data['set_status_name'] ?? 'Escalated')),
            trim((string)($data['comment_template'] ?? 'Ticket auto-escalated.')),
            !empty($data['is_active']) ? 1 : 0,
            max(0, (int)($data['sort_order'] ?? 100)),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateRecord(int $id, array $data): void {
        $stmt = $this->db->prepare('UPDATE support_escalation_rules SET rule_name = ?, priority_name = ?, category_name = ?, hours_after_breach = ?, escalate_to_user_id = ?, set_priority_name = ?, set_status_name = ?, comment_template = ?, is_active = ?, sort_order = ?, updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([
            trim((string)($data['rule_name'] ?? '')),
            trim((string)($data['priority_name'] ?? '')),
            trim((string)($data['category_name'] ?? '')),
            max(0, (int)($data['hours_after_breach'] ?? 0)),
            !empty($data['escalate_to_user_id']) ? (int)$data['escalate_to_user_id'] : null,
            trim((string)($data['set_priority_name'] ?? '')),
            trim((string)($data['set_status_name'] ?? 'Escalated')),
            trim((string)($data['comment_template'] ?? 'Ticket auto-escalated.')),
            !empty($data['is_active']) ? 1 : 0,
            max(0, (int)($data['sort_order'] ?? 100)),
            $id,
            current_company_id(),
        ]);
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM support_escalation_rules WHERE id = ? AND company_id = ?');
        $stmt->execute([$id, current_company_id()]);
    }
}
