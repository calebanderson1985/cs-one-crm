<?php
namespace App\Models;

class Setting extends BaseModel {
    public function list(): array {
        $stmt = $this->db->prepare('SELECT * FROM system_settings WHERE company_id = ? ORDER BY setting_key ASC');
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function upsertMany(array $rows): void {
        $stmt = $this->db->prepare('INSERT INTO system_settings (company_id, setting_key, setting_value, updated_at) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=VALUES(updated_at)');
        foreach ($rows as $key => $value) {
            $stmt->execute([current_company_id(), $key, $value]);
        }
    }
}
