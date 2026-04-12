<?php
namespace App\Models;

class Commission extends BaseModel {
    public function list(): array { return $this->all('commissions', 'created_at DESC'); }
    public function get(int $id): ?array { return $this->find('commissions', $id); }
    public function create(array $data): int {
        $agentUserId = !empty($data['agent_user_id']) ? (int) $data['agent_user_id'] : null;
        $agentName = $agentUserId ? $this->userNameById($agentUserId) : ($data['agent_name'] ?? null);
        $stmt = $this->db->prepare('INSERT INTO commissions (company_id, agent_user_id, agent_name, client_name, deal_name, amount, payout_status, statement_month, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([current_company_id(), $agentUserId, $agentName, $data['client_name'], $data['deal_name'], (float)($data['amount'] ?? 0), $data['payout_status'], $data['statement_month'] ?: null, $data['notes'] ?: null]);
        return (int) $this->db->lastInsertId();
    }
    public function update(int $id, array $data): void {
        $agentUserId = !empty($data['agent_user_id']) ? (int) $data['agent_user_id'] : null;
        $agentName = $agentUserId ? $this->userNameById($agentUserId) : ($data['agent_name'] ?? null);
        $stmt = $this->db->prepare('UPDATE commissions SET agent_user_id=?, agent_name=?, client_name=?, deal_name=?, amount=?, payout_status=?, statement_month=?, notes=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([$agentUserId, $agentName, $data['client_name'], $data['deal_name'], (float)($data['amount'] ?? 0), $data['payout_status'], $data['statement_month'] ?: null, $data['notes'] ?: null, $id, current_company_id()]);
    }
    public function delete(int $id): void { $this->deleteRecord('commissions', $id); }
}
