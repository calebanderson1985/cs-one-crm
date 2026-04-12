<?php
namespace App\Models;

class Company extends BaseModel {
    public function listAll(): array {
        $stmt = $this->db->query("SELECT id, company_name, tenant_key, status, suspension_reason, suspended_at, created_at FROM companies ORDER BY company_name ASC");
        return $stmt->fetchAll();
    }

    public function setStatus(int $id, string $status, ?string $reason = null): void {
        $sql = "UPDATE companies SET status = ?, suspension_reason = ?, suspended_at = CASE WHEN ? = 'Suspended' THEN NOW() ELSE NULL END, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $reason, $status, $id]);
    }

    public function isActive(int $id): bool {
        $stmt = $this->db->prepare('SELECT status FROM companies WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return (string) $stmt->fetchColumn() === 'Active';
    }
}
