<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Services\WorkflowEngine;

class WorkflowController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('workflows', 'view');
        $workflowModel = new Workflow($this->db);
        $runModel = new WorkflowRun($this->db);
        $engine = new WorkflowEngine($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                Auth::requirePermission('workflows', 'create');
                $id = $workflowModel->create($_POST);
                audit_log($this->db, 'workflows', 'create', $id, 'Workflow created');
                flash('success', 'Workflow created.');
            }
            if ($action === 'update') {
                Auth::requirePermission('workflows', 'edit');
                $id = (int) $_POST['id'];
                $workflowModel->update($id, $_POST);
                audit_log($this->db, 'workflows', 'update', $id, 'Workflow updated');
                flash('success', 'Workflow updated.');
            }
            if ($action === 'delete') {
                Auth::requirePermission('workflows', 'delete');
                $id = (int) $_POST['id'];
                $workflowModel->delete($id);
                audit_log($this->db, 'workflows', 'delete', $id, 'Workflow deleted');
                flash('success', 'Workflow deleted.');
            }
            if ($action === 'run') {
                Auth::requirePermission('workflows', 'edit');
                $workflow = $workflowModel->get((int) ($_POST['id'] ?? 0));
                if ($workflow) {
                    $payload = [
                        'manual' => true,
                        'record' => [
                            'id' => 0,
                            'type' => 'Workflow',
                            'lead_name' => 'Manual Run',
                            'company_name' => 'Manual Queue',
                            'email' => setting($this->db, 'email_from_address', 'demo@example.com'),
                            'phone' => setting($this->db, 'sms_from_number', '+10000000000'),
                            'stage' => 'New',
                        ],
                    ];
                    $engine->enqueueWorkflow($workflow, $payload);
                    flash('success', 'Workflow execution queued.');
                }
            }
            if ($action === 'process_queue') {
                Auth::requirePermission('workflows', 'edit');
                $results = $engine->processQueue(15);
                $done = count(array_filter($results, fn ($row) => ($row['status'] ?? '') === 'Success'));
                flash('success', 'Workflow queue processed. Successful jobs: ' . $done . '.');
            }
            redirect('index.php?page=workflows');
        }
        $workflows = $workflowModel->list();
        $runs = $runModel->list();
        $editWorkflow = request_id() ? $workflowModel->get(request_id()) : null;
        $queue = $engine->queueSnapshot();
        View::render('workflows/index', compact('workflows', 'runs', 'editWorkflow', 'queue'));
    }
}
