<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Setting;

class BrandingController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('branding', 'view');
        $model = new Setting($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            Auth::requirePermission('branding', 'edit');
            $model->upsertMany([
                'app_name' => trim($_POST['app_name'] ?? 'CS One CRM Phase 7'),
                'brand_support_email' => trim($_POST['brand_support_email'] ?? ''),
                'accent_color' => trim($_POST['accent_color'] ?? '#0f62fe'),
                'login_headline' => trim($_POST['login_headline'] ?? 'All in one CRM for growth teams'),
                'footer_branding' => trim($_POST['footer_branding'] ?? 'Powered by CS One CRM'),
                'company_tagline' => trim($_POST['company_tagline'] ?? ''),
            ]);
            audit_log($this->db, 'branding', 'update', null, 'Branding settings updated');
            flash('success', 'Branding updated.');
            redirect('index.php?page=branding');
        }
        $settings = $model->list();
        $settingsMap = [];
        foreach ($settings as $row) {
            $settingsMap[$row['setting_key']] = $row['setting_value'];
        }
        View::render('admin/branding', compact('settingsMap'));
    }
}
