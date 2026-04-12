<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\ApiRequestLog;

class ApiAnalyticsController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('api_analytics', 'view');
        $model = new ApiRequestLog($this->db);
        if (($_GET['export'] ?? '') === 'csv') {
            $this->exportCsv($model->listRecent(500));
            return;
        }
        $summary = $model->summary();
        $requests = $model->listRecent(100);
        $scopeSummary = $model->scopeSummary();
        View::render('admin/api_analytics', compact('summary', 'requests', 'scopeSummary'));
    }

    private function exportCsv(array $rows): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="api-analytics.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Resource','Method','Status','Path','IP','Scopes','Created At']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['id'] ?? '',
                $row['resource_name'] ?? '',
                $row['http_method'] ?? '',
                $row['status_code'] ?? '',
                $row['request_path'] ?? '',
                $row['ip_address'] ?? '',
                $row['scope_text'] ?? '',
                $row['created_at'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
