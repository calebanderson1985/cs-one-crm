<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Setting;

class SettingsController {
    public function __construct(private \PDO $db) {}
    public function index(): void {
        Auth::requirePermission('settings', 'view');
        $model = new Setting($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $model->upsertMany([
                'app_name' => $_POST['app_name'] ?? 'CS One CRM',
                'default_timezone' => $_POST['default_timezone'] ?? 'America/Chicago',
                'email_provider' => $_POST['email_provider'] ?? '',
                'email_from_address' => $_POST['email_from_address'] ?? '',
                'email_api_key' => $_POST['email_api_key'] ?? '',
                'email_domain' => $_POST['email_domain'] ?? '',
                'sms_provider' => $_POST['sms_provider'] ?? '',
                'sms_account_sid' => $_POST['sms_account_sid'] ?? '',
                'sms_auth_token' => $_POST['sms_auth_token'] ?? '',
                'sms_from_number' => $_POST['sms_from_number'] ?? '',
                'tenant_mode' => $_POST['tenant_mode'] ?? 'tenant-isolated',
                'ai_provider' => $_POST['ai_provider'] ?? 'heuristic',
                'ai_api_key' => $_POST['ai_api_key'] ?? '',
                'api_token' => $_POST['api_token'] ?? 'change-me',
                'worker_batch_size' => $_POST['worker_batch_size'] ?? '25',
                'stripe_mode' => $_POST['stripe_mode'] ?? 'test',
                'stripe_public_key' => $_POST['stripe_public_key'] ?? '',
                'stripe_secret_key' => $_POST['stripe_secret_key'] ?? '',
                'stripe_webhook_secret' => $_POST['stripe_webhook_secret'] ?? '',
                'billing_checkout_success_url' => $_POST['billing_checkout_success_url'] ?? '',
                'billing_checkout_cancel_url' => $_POST['billing_checkout_cancel_url'] ?? '',
                'login_rate_limit' => $_POST['login_rate_limit'] ?? '5',
            ]);
            audit_log($this->db, 'settings', 'update', null, 'System settings updated');
            flash('success', 'Settings saved.');
            redirect('index.php?page=settings');
        }
        $settings = $model->list();
        $settingsMap = [];
        foreach ($settings as $row) { $settingsMap[$row['setting_key']] = $row['setting_value']; }
        View::render('admin/settings', compact('settingsMap'));
    }
}
