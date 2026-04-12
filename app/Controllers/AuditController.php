<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\AuditLog;

class AuditController {
    public function __construct(private \PDO $db) {}
    public function index(): void {
        Auth::requirePermission('audit', 'view');
        $filters = [
            'module' => trim((string)($_GET['module'] ?? '')),
            'action' => trim((string)($_GET['action'] ?? '')),
            'user_id' => trim((string)($_GET['user_id'] ?? '')),
            'date_from' => trim((string)($_GET['date_from'] ?? '')),
            'date_to' => trim((string)($_GET['date_to'] ?? '')),
            'q' => trim((string)($_GET['q'] ?? '')),
        ];
        $logs = (new AuditLog($this->db))->list($filters);
        if (($_GET['export'] ?? '') === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=audit-trail.csv');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['When','Module','Action','Record','User','Summary','IP']);
            foreach ($logs as $row) {
                fputcsv($out, [$row['created_at'],$row['module_name'],$row['action_name'],$row['record_id'] ?? '',$row['user_id'] ?? '',$row['summary_text'] ?? '',$row['ip_address'] ?? '']);
            }
            fclose($out);
            exit;
        }
        View::render('admin/audit', compact('logs', 'filters'));
    }
}
