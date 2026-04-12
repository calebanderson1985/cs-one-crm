<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\QueueOps;

class QueueOpsController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('queue_ops', 'view');
        $model = new QueueOps($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            Auth::requirePermission('queue_ops', 'edit');
            $action = (string) ($_POST['action'] ?? '');
            $id = (int) ($_POST['id'] ?? 0);
            if ($action === 'retry_workflow') {
                $model->retryWorkflow($id);
                audit_log($this->db, 'queue_ops', 'retry_workflow', $id, 'Workflow job requeued');
                flash('success', 'Workflow job requeued.');
            }
            if ($action === 'retry_outbound') {
                $model->retryOutbound($id);
                audit_log($this->db, 'queue_ops', 'retry_outbound', $id, 'Outbound message queued for retry');
                flash('success', 'Outbound message queued for retry.');
            }
            redirect('index.php?page=queue_ops');
        }
        $summary = $model->summary();
        $workflowFailed = $model->workflowFailed();
        $outboundFailed = $model->outboundFailed();
        View::render('admin/queue_ops', compact('summary', 'workflowFailed', 'outboundFailed'));
    }
}
