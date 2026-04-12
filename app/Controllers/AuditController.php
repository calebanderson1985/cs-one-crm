<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\AuditLog;

class AuditController {
    public function __construct(private \PDO $db) {}
    public function index(): void {
        Auth::requirePermission('audit', 'view');
        $logs = (new AuditLog($this->db))->list();
        View::render('admin/audit', compact('logs'));
    }
}
