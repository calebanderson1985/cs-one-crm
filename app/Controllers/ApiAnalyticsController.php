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
        $summary = $model->summary();
        $requests = $model->listRecent(100);
        View::render('admin/api_analytics', compact('summary', 'requests'));
    }
}
