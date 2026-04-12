<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

class DiagnosticsController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('diagnostics', 'view');
        $checks = [
            ['label' => 'PHP version', 'status' => version_compare(PHP_VERSION, '8.0.0', '>=') ? 'pass' : 'warn', 'detail' => PHP_VERSION],
            ['label' => 'PDO MySQL', 'status' => extension_loaded('pdo_mysql') ? 'pass' : 'fail', 'detail' => extension_loaded('pdo_mysql') ? 'loaded' : 'missing'],
            ['label' => 'Uploads dir writable', 'status' => is_writable(dirname(__DIR__, 2) . '/storage') ? 'pass' : 'warn', 'detail' => dirname(__DIR__, 2) . '/storage'],
            ['label' => 'Worker batch size', 'status' => ((int) setting($this->db, 'worker_batch_size', '25')) > 0 ? 'pass' : 'warn', 'detail' => setting($this->db, 'worker_batch_size', '25')],
            ['label' => 'API rate limit', 'status' => ((int) setting($this->db, 'api_rate_limit_per_minute', '60')) > 0 ? 'pass' : 'warn', 'detail' => setting($this->db, 'api_rate_limit_per_minute', '60') . ' req/min'],
            ['label' => 'Password policy length', 'status' => ((int) setting($this->db, 'password_policy_min_length', '10')) >= 8 ? 'pass' : 'warn', 'detail' => setting($this->db, 'password_policy_min_length', '10')],
        ];
        View::render('admin/diagnostics', compact('checks'));
    }
}
