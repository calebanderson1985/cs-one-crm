<?php
namespace App\Models;

class Deal extends BaseModel {
    public function list(): array { return $this->all('deals', 'updated_at DESC'); }
    public function get(int $id): ?array { return $this->find('deals', $id); }
    public function create(array $data): int {
        $ownerUserId = !empty($data['owner_user_id']) ? (int) $data['owner_user_id'] : null;
        $ownerName = $ownerUserId ? $this->userNameById($ownerUserId) : null;
        $stmt = $this->db->prepare('INSERT INTO deals (company_id, deal_name, client_name, stage, amount, owner_user_id, owner_name, close_date, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([current_company_id(), $data['deal_name'], $data['client_name'], $data['stage'], (float)($data['amount'] ?? 0), $ownerUserId, $ownerName, $data['close_date'] ?: null, $data['notes'] ?: null]);
        return (int) $this->db->lastInsertId();
    }
    public function update(int $id, array $data): void {
        $ownerUserId = !empty($data['owner_user_id']) ? (int) $data['owner_user_id'] : null;
        $ownerName = $ownerUserId ? $this->userNameById($ownerUserId) : null;
        $stmt = $this->db->prepare('UPDATE deals SET deal_name=?, client_name=?, stage=?, amount=?, owner_user_id=?, owner_name=?, close_date=?, notes=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([$data['deal_name'], $data['client_name'], $data['stage'], (float)($data['amount'] ?? 0), $ownerUserId, $ownerName, $data['close_date'] ?: null, $data['notes'] ?: null, $id, current_company_id()]);
    }
    public function delete(int $id): void { $this->deleteRecord('deals', $id); }
}
