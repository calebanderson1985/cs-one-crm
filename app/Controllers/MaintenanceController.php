<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\MaintenanceRun;

class MaintenanceController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('maintenance', 'view');
        $runsModel = new MaintenanceRun($this->db);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            Auth::requirePermission('maintenance', 'edit');
            $action = $_POST['action'] ?? 'save';
            if ($action === 'save') {
                (new \App\Models\Setting($this->db))->upsertMany([
                    'retention_audit_days' => $_POST['retention_audit_days'] ?? '180',
                    'retention_api_days' => $_POST['retention_api_days'] ?? '90',
                    'retention_webhook_days' => $_POST['retention_webhook_days'] ?? '30',
                    'retention_outbound_days' => $_POST['retention_outbound_days'] ?? '60',
                ]);
                audit_log($this->db, 'maintenance', 'update', null, 'Retention settings updated');
                flash('success', 'Maintenance settings saved.');
            } elseif ($action === 'cleanup') {
                $result = $this->runCleanup();
                $runsModel->create('cleanup', $result);
                audit_log($this->db, 'maintenance', 'cleanup', null, 'Maintenance cleanup executed');
                flash('success', 'Cleanup completed.');
            } elseif ($action === 'snapshot') {
                $result = $this->createSnapshot();
                $runsModel->create('snapshot', $result);
                audit_log($this->db, 'maintenance', 'snapshot', null, 'Configuration snapshot created');
                flash('success', 'Configuration snapshot saved.');
            }
            redirect('index.php?page=maintenance');
        }

        $retention = [
            'audit' => setting($this->db, 'retention_audit_days', '180'),
            'api' => setting($this->db, 'retention_api_days', '90'),
            'webhooks' => setting($this->db, 'retention_webhook_days', '30'),
            'outbound' => setting($this->db, 'retention_outbound_days', '60'),
        ];
        $runs = $runsModel->listRecent();
        View::render('admin/maintenance', compact('retention', 'runs'));
    }

    private function runCleanup(): array {
        $companyId = current_company_id();
        $tables = [
            'audit_logs' => max(7, (int)setting($this->db, 'retention_audit_days', '180')),
            'api_request_logs' => max(7, (int)setting($this->db, 'retention_api_days', '90')),
            'webhook_events' => max(1, (int)setting($this->db, 'retention_webhook_days', '30')),
            'outbound_messages' => max(1, (int)setting($this->db, 'retention_outbound_days', '60')),
        ];
        $results = [];
        foreach ($tables as $table => $days) {
            $sql = "DELETE FROM {$table} WHERE company_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL {$days} DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$companyId]);
            $results[$table] = $stmt->rowCount();
        }
        return $results;
    }

    private function createSnapshot(): array {
        $stmt = $this->db->prepare('SELECT setting_key, setting_value FROM system_settings WHERE company_id = ? ORDER BY setting_key ASC');
        $stmt->execute([current_company_id()]);
        $settings = $stmt->fetchAll();
        $dir = dirname(__DIR__, 2) . '/storage/snapshots';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $filename = 'settings-company-' . current_company_id() . '-' . date('Ymd-His') . '.json';
        $path = $dir . '/' . $filename;
        file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return ['file' => $filename, 'count' => count($settings)];
    }
}
