<?php
namespace App\Models;

class Task extends BaseModel {
    public function list(): array { return $this->all('tasks', 'due_date IS NULL, due_date ASC, id DESC'); }
    public function get(int $id): ?array { return $this->find('tasks', $id); }
    public function create(array $data): int {
        $assignedUserId = !empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null;
        $assignedName = $assignedUserId ? $this->userNameById($assignedUserId) : null;
        $stmt = $this->db->prepare('INSERT INTO tasks (company_id, task_name, related_type, related_name, assigned_user_id, assigned_to, priority_level, due_date, status, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([current_company_id(), $data['task_name'], $data['related_type'] ?: null, $data['related_name'] ?: null, $assignedUserId, $assignedName, $data['priority_level'], $data['due_date'] ?: null, $data['status'], $data['notes'] ?: null]);
        return (int) $this->db->lastInsertId();
    }
    public function update(int $id, array $data): void {
        $assignedUserId = !empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null;
        $assignedName = $assignedUserId ? $this->userNameById($assignedUserId) : null;
        $stmt = $this->db->prepare('UPDATE tasks SET task_name=?, related_type=?, related_name=?, assigned_user_id=?, assigned_to=?, priority_level=?, due_date=?, status=?, notes=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([$data['task_name'], $data['related_type'] ?: null, $data['related_name'] ?: null, $assignedUserId, $assignedName, $data['priority_level'], $data['due_date'] ?: null, $data['status'], $data['notes'] ?: null, $id, current_company_id()]);
    }
    public function delete(int $id): void { $this->deleteRecord('tasks', $id); }
}
