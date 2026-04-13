<?php
namespace App\Models;

class Client extends BaseModel {
    public function list(): array { return $this->all('clients', 'company_name ASC'); }
    public function get(int $id): ?array { return $this->find('clients', $id); }
    public function create(array $data): int {
        $assignedUserId = !empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null;
        $assignedName = $assignedUserId ? $this->userNameById($assignedUserId) : null;
        $stmt = $this->db->prepare('INSERT INTO clients (company_id, company_name, contact_name, email, phone, status, lifecycle_stage, industry_name, website_url, policy_type, renewal_date, address_line1, address_line2, city_name, state_name, postal_code, annual_revenue, employee_count, assigned_user_id, assigned_agent, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([current_company_id(), $data['company_name'], $data['contact_name'], $data['email'] ?: null, $data['phone'] ?: null, $data['status'], $data['lifecycle_stage'] ?: null, $data['industry_name'] ?: null, $data['website_url'] ?: null, $data['policy_type'] ?: null, $data['renewal_date'] ?: null, $data['address_line1'] ?: null, $data['address_line2'] ?: null, $data['city_name'] ?: null, $data['state_name'] ?: null, $data['postal_code'] ?: null, $data['annual_revenue'] ?: null, $data['employee_count'] ?: null, $assignedUserId, $assignedName, $data['notes'] ?: null]);
        return (int) $this->db->lastInsertId();
    }
    public function update(int $id, array $data): void {
        $assignedUserId = !empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null;
        $assignedName = $assignedUserId ? $this->userNameById($assignedUserId) : null;
        $stmt = $this->db->prepare('UPDATE clients SET company_name=?, contact_name=?, email=?, phone=?, status=?, lifecycle_stage=?, industry_name=?, website_url=?, policy_type=?, renewal_date=?, address_line1=?, address_line2=?, city_name=?, state_name=?, postal_code=?, annual_revenue=?, employee_count=?, assigned_user_id=?, assigned_agent=?, notes=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([$data['company_name'], $data['contact_name'], $data['email'] ?: null, $data['phone'] ?: null, $data['status'], $data['lifecycle_stage'] ?: null, $data['industry_name'] ?: null, $data['website_url'] ?: null, $data['policy_type'] ?: null, $data['renewal_date'] ?: null, $data['address_line1'] ?: null, $data['address_line2'] ?: null, $data['city_name'] ?: null, $data['state_name'] ?: null, $data['postal_code'] ?: null, $data['annual_revenue'] ?: null, $data['employee_count'] ?: null, $assignedUserId, $assignedName, $data['notes'] ?: null, $id, current_company_id()]);
    }
    public function delete(int $id): void { $this->deleteRecord('clients', $id); }
}
