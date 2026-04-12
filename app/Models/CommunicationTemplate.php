<?php
namespace App\Models;

class CommunicationTemplate extends BaseModel {
    public function list(): array {
        return $this->all('communication_templates', 'template_name ASC');
    }
    public function get(int $id): ?array {
        return $this->find('communication_templates', $id);
    }
    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO communication_templates (company_id, template_name, channel, subject_template, body_template, status, created_at, updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([current_company_id(), $data['template_name'], $data['channel'], $data['subject_template'] ?: null, $data['body_template'], $data['status']]);
        return (int) $this->db->lastInsertId();
    }
    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare('UPDATE communication_templates SET template_name=?, channel=?, subject_template=?, body_template=?, status=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([$data['template_name'], $data['channel'], $data['subject_template'] ?: null, $data['body_template'], $data['status'], $id, current_company_id()]);
    }
    public function delete(int $id): void {
        $this->deleteRecord('communication_templates', $id);
    }
}
