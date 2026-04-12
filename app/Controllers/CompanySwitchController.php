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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $target = (int)($_POST['company_id'] ?? 1);
            if ($target <= 1) {
                unset($_SESSION['impersonated_company_id']);
                flash('success', 'Returned to primary company context.');
            } else {
                $_SESSION['impersonated_company_id'] = $target;
                flash('success', 'Switched company context.');
            }
            audit_log($this->db, 'company_switch', 'switch', $target ?: 1, 'Company context updated');
            redirect('index.php?page=company_switch');
        }
        $companies = (new Company($this->db))->listAll();
        $activeCompanyId = current_company_id();
        View::render('admin/company_switch', compact('companies', 'activeCompanyId'));
    }
}
