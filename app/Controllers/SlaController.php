<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\SlaPolicy;
use App\Models\SupportTicket;
use App\Models\SupportEscalationRule;
use App\Models\User;

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
            } elseif ($action === 'rule_create') {
                Auth::requirePermission('sla', 'create');
                $id = (new SupportEscalationRule($this->db))->create($_POST);
                audit_log($this->db, 'sla', 'rule_create', $id, 'Escalation rule created');
                flash('success', 'Escalation rule created.');
            } elseif ($action === 'rule_update') {
                Auth::requirePermission('sla', 'edit');
                $id = (int)($_POST['id'] ?? 0);
                (new SupportEscalationRule($this->db))->updateRecord($id, $_POST);
                audit_log($this->db, 'sla', 'rule_update', $id, 'Escalation rule updated');
                flash('success', 'Escalation rule updated.');
            } elseif ($action === 'rule_delete') {
                Auth::requirePermission('sla', 'delete');
                $id = (int)($_POST['id'] ?? 0);
                (new SupportEscalationRule($this->db))->delete($id);
                audit_log($this->db, 'sla', 'rule_delete', $id, 'Escalation rule deleted');
                flash('success', 'Escalation rule deleted.');
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
        $rules = (new SupportEscalationRule($this->db))->list($filters);
        $users = (new User($this->db))->list();
        View::render('admin/sla', compact('policies', 'filters', 'summary', 'rules', 'users'));
    }
}
