<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Company;

class CompanySwitchController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('company_switch', 'view');
        if (!is_super_admin()) {
            http_response_code(403);
            exit('Forbidden');
        }
        $companyModel = new Company($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'switch';
            if ($action === 'switch') {
                $target = (int)($_POST['company_id'] ?? 1);
                if ($target <= 1) {
                    unset($_SESSION['impersonated_company_id']);
                    flash('success', 'Returned to primary company context.');
                } else {
                    $_SESSION['impersonated_company_id'] = $target;
                    flash('success', 'Switched company context.');
                }
                audit_log($this->db, 'company_switch', 'switch', $target ?: 1, 'Company context updated');
            } elseif ($action === 'suspend') {
                $id = (int)($_POST['id'] ?? 0);
                $reason = trim((string)($_POST['reason'] ?? 'Administrative suspension'));
                $companyModel->setStatus($id, 'Suspended', $reason);
                audit_log($this->db, 'companies', 'suspend', $id, 'Company suspended');
                flash('success', 'Company suspended.');
            } elseif ($action === 'reactivate') {
                $id = (int)($_POST['id'] ?? 0);
                $companyModel->setStatus($id, 'Active', null);
                audit_log($this->db, 'companies', 'reactivate', $id, 'Company reactivated');
                flash('success', 'Company reactivated.');
            }
            redirect('index.php?page=company_switch');
        }
        $companies = $companyModel->listAll();
        $activeCompanyId = current_company_id();
        View::render('admin/company_switch', compact('companies', 'activeCompanyId'));
    }
}
