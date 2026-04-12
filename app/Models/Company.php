<?php
namespace App\Models;

class Company extends BaseModel {
    public function listAll(): array {
        $stmt = $this->db->query('SELECT id, company_name, company_slug, is_active, created_at FROM companies ORDER BY company_name ASC');
        return $stmt->fetchAll();
    }
}
