<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use App\Services\WorkflowEngine;

class TaskController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('tasks', 'view');
        $model = new Task($this->db);
        $userModel = new User($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('tasks', 'create');
                $id = $model->create($_POST);
                $record = $model->get($id);
                $this->notifyAssignee($record);
                (new WorkflowEngine($this->db))->onEvent('task.created', ['record' => ($record ?: []) + ['type' => 'Task']]);
                audit_log($this->db, 'tasks', 'create', $id, 'Task created');
                flash('success', 'Task created.');
            }
            if ($action === 'update') {
                Auth::requirePermission('tasks', 'edit');
                $id = (int) $_POST['id'];
                $model->update($id, $_POST);
                $record = $model->get($id);
                $this->notifyAssignee($record, 'Task updated');
                (new WorkflowEngine($this->db))->onEvent('task.updated', ['record' => ($record ?: []) + ['type' => 'Task']]);
                audit_log($this->db, 'tasks', 'update', $id, 'Task updated');
                flash('success', 'Task updated.');
            }
            if ($action === 'delete') {
                Auth::requirePermission('tasks', 'delete');
                $id = (int) $_POST['id'];
                $model->delete($id);
                audit_log($this->db, 'tasks', 'delete', $id, 'Task deleted');
                flash('success', 'Task deleted.');
            }
            redirect('index.php?page=tasks');
        }
        $tasks = $model->list();
        $editTask = request_id() ? $model->get(request_id()) : null;
        $assignableUsers = $userModel->assigneeOptions();
        View::render('tasks/index', compact('tasks', 'editTask', 'assignableUsers'));
    }

    private function notifyAssignee(?array $record, string $title = 'Task assigned'): void {
        if (!$record || empty($record['assigned_user_id'])) {
            return;
        }
        (new Notification($this->db))->create([
            'user_id' => (int) $record['assigned_user_id'],
            'title' => $title,
            'message_text' => ($record['task_name'] ?? 'Task') . ' is assigned to you.',
            'level_name' => 'info',
            'link_url' => 'index.php?page=tasks&id=' . (int) $record['id'],
        ]);
    }
}
