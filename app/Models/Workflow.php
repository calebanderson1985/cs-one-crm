<?php
namespace App\Models;

class Workflow extends BaseModel {
    public function list(): array { return $this->all('workflows', 'workflow_name ASC'); }
    public function get(int $id): ?array { return $this->find('workflows', $id); }
    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO workflows (company_id, workflow_name, module_name, description_text, trigger_key, condition_field, condition_operator, condition_value, action_key, action_payload, run_mode, status, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([current_company_id(), $data['workflow_name'], $data['module_name'], $data['description_text'] ?: null, $data['trigger_key'], $data['condition_field'] ?: null, $data['condition_operator'] ?: null, $data['condition_value'] ?: null, $data['action_key'], $data['action_payload'] ?: null, $data['run_mode'] ?: 'queue', $data['status'], current_user_id() ?: null]);
        return (int) $this->db->lastInsertId();
    }
    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare('UPDATE workflows SET workflow_name=?, module_name=?, description_text=?, trigger_key=?, condition_field=?, condition_operator=?, condition_value=?, action_key=?, action_payload=?, run_mode=?, status=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([$data['workflow_name'], $data['module_name'], $data['description_text'] ?: null, $data['trigger_key'], $data['condition_field'] ?: null, $data['condition_operator'] ?: null, $data['condition_value'] ?: null, $data['action_key'], $data['action_payload'] ?: null, $data['run_mode'] ?: 'queue', $data['status'], $id, current_company_id()]);
    }
    public function delete(int $id): void { $this->deleteRecord('workflows', $id); }
    public function activeByTrigger(string $triggerKey): array {
        $stmt = $this->db->prepare("SELECT * FROM workflows WHERE company_id = ? AND trigger_key = ? AND status = 'Active' ORDER BY id ASC");
        $stmt->execute([current_company_id(), $triggerKey]);
        return $stmt->fetchAll();
    }
}
