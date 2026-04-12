<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\SlaPolicy;
use App\Models\SupportTicket;

class SlaController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('sla', 'view');
        $model = new SlaPolicy($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('sla', 'create');
                $id = $model->create($_POST);
                audit_log($this->db, 'sla', 'create', $id, 'SLA policy created');
                flash('success', 'SLA policy created.');
            } elseif ($action === 'update') {
                Auth::requirePermission('sla', 'edit');
                $id = (int)($_POST['id'] ?? 0);
                $model->updateRecord($id, $_POST);
                audit_log($this->db, 'sla', 'update', $id, 'SLA policy updated');
                flash('success', 'SLA policy updated.');
            } elseif ($action === 'delete') {
                Auth::requirePermission('sla', 'delete');
                $id = (int)($_POST['id'] ?? 0);
                $model->delete($id);
                audit_log($this->db, 'sla', 'delete', $id, 'SLA policy deleted');
                flash('success', 'SLA policy deleted.');
            }
            redirect('index.php?page=sla');
        }
        $filters = ['q' => trim((string)($_GET['q'] ?? ''))];
        $policies = $model->list($filters);
        $summary = (new SupportTicket($this->db))->slaSummary();
        View::render('admin/sla', compact('policies', 'filters', 'summary'));
    }
}
