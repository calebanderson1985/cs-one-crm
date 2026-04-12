<?php
namespace App\Models;

class Lead extends BaseModel {
    public function list(): array { return $this->all('leads', 'created_at DESC'); }
    public function get(int $id): ?array { return $this->find('leads', $id); }
    public function create(array $data): int {
        $assignedUserId = !empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null;
        $assignedName = $assignedUserId ? $this->userNameById($assignedUserId) : null;
        $stmt = $this->db->prepare('INSERT INTO leads (company_id, lead_name, company_name, email, phone, source_name, stage, assigned_user_id, assigned_to, ai_score, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([current_company_id(), $data['lead_name'], $data['company_name'] ?: null, $data['email'] ?: null, $data['phone'] ?: null, $data['source_name'] ?: null, $data['stage'], $assignedUserId, $assignedName, (int)($data['ai_score'] ?? 0), $data['notes'] ?: null]);
        return (int) $this->db->lastInsertId();
    }
    public function update(int $id, array $data): void {
        $assignedUserId = !empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null;
        $assignedName = $assignedUserId ? $this->userNameById($assignedUserId) : null;
        $stmt = $this->db->prepare('UPDATE leads SET lead_name=?, company_name=?, email=?, phone=?, source_name=?, stage=?, assigned_user_id=?, assigned_to=?, ai_score=?, notes=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([$data['lead_name'], $data['company_name'] ?: null, $data['email'] ?: null, $data['phone'] ?: null, $data['source_name'] ?: null, $data['stage'], $assignedUserId, $assignedName, (int)($data['ai_score'] ?? 0), $data['notes'] ?: null, $id, current_company_id()]);
    }
    public function setScore(int $id, int $score): void {
        $stmt = $this->db->prepare('UPDATE leads SET ai_score = ?, last_scored_at = NOW(), updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([$score, $id, current_company_id()]);
    }
    public function convertToClient(int $id): ?int {
        $lead = $this->get($id);
        if (!$lead) { return null; }
        $stmt = $this->db->prepare('INSERT INTO clients (company_id, company_name, contact_name, email, phone, status, assigned_user_id, assigned_agent, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            current_company_id(),
            $lead['company_name'] ?: $lead['lead_name'],
            $lead['lead_name'],
            $lead['email'],
            $lead['phone'],
            'Active',
            $lead['assigned_user_id'],
            $lead['assigned_to'],
            'Converted from lead #' . $lead['id'] . ($lead['notes'] ? ' - ' . $lead['notes'] : ''),
        ]);
        $clientId = (int) $this->db->lastInsertId();
        $stmt = $this->db->prepare("UPDATE leads SET stage='Converted', updated_at=NOW() WHERE id=? AND company_id=?");
        $stmt->execute([$id, current_company_id()]);
        return $clientId;
    }
    public function delete(int $id): void { $this->deleteRecord('leads', $id); }
}
