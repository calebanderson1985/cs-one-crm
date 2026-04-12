<?php
$error = null;
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? 'localhost');
    $port = trim($_POST['port'] ?? '3306');
    $database = trim($_POST['database'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $adminName = trim($_POST['admin_name'] ?? 'Administrator');
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@example.com');
    $adminPassword = $_POST['admin_password'] ?? '';
    $tenantName = trim($_POST['tenant_name'] ?? 'Default Tenant');
    $tenantKey = trim($_POST['tenant_key'] ?? 'default-tenant');
    $appName = trim($_POST['app_name'] ?? 'CS One CRM Phase 7');
    $emailFrom = trim($_POST['email_from_address'] ?? 'noreply@example.com');
    $supportEmail = trim($_POST['brand_support_email'] ?? 'support@example.com');
    $accentColor = trim($_POST['accent_color'] ?? '#0f62fe');
    $seedDemo = !empty($_POST['seed_demo']);

    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);
        $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $pdo->exec(file_get_contents(__DIR__ . '/database/schema.sql'));

        $stmt = $pdo->prepare('INSERT INTO companies (company_name, tenant_key, status, created_at, updated_at) VALUES (?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE company_name=VALUES(company_name), updated_at=VALUES(updated_at)');
        $stmt->execute([$tenantName, $tenantKey, 'Active']);
        $companyIdStmt = $pdo->prepare('SELECT id FROM companies WHERE tenant_key = ? LIMIT 1');
        $companyIdStmt->execute([$tenantKey]);
        $companyId = (int) $companyIdStmt->fetchColumn();

        $adminStmt = $pdo->prepare('SELECT id FROM users WHERE company_id = ? AND email = ? LIMIT 1');
        $adminStmt->execute([$companyId, $adminEmail]);
        $adminId = (int) $adminStmt->fetchColumn();
        if ($adminId > 0) {
            $update = $pdo->prepare('UPDATE users SET full_name=?, password_hash=?, role=\'admin\', is_active=1, updated_at=NOW() WHERE id=?');
            $update->execute([$adminName, password_hash($adminPassword, PASSWORD_DEFAULT), $adminId]);
        } else {
            $insert = $pdo->prepare('INSERT INTO users (company_id, full_name, email, password_hash, role, manager_user_id, portal_client_id, is_active, created_at, updated_at) VALUES (?,?,?,?,\'admin\',NULL,NULL,1,NOW(),NOW())');
            $insert->execute([$companyId, $adminName, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT)]);
            $adminId = (int) $pdo->lastInsertId();
        }

        require __DIR__ . '/app/Services/PermissionService.php';
        App\Services\PermissionService::seedDefaults($pdo, $companyId);

        $settings = [
            'app_name' => $appName,
            'default_timezone' => 'America/Chicago',
            'tenant_mode' => 'tenant-isolated',
            'email_provider' => 'PHP Mail',
            'email_from_address' => $emailFrom,
            'email_api_key' => '',
            'email_domain' => '',
            'sms_provider' => 'Twilio',
            'sms_account_sid' => '',
            'sms_auth_token' => '',
            'sms_from_number' => '+15555550123',
            'ai_provider' => 'heuristic',
            'ai_api_key' => '',
            'api_token' => bin2hex(random_bytes(16)),
            'worker_batch_size' => '25',
            'brand_support_email' => $supportEmail,
            'accent_color' => $accentColor,
            'login_headline' => 'All in one CRM for growth teams',
            'footer_branding' => 'Powered by CS One CRM',
            'company_tagline' => 'Commercial-ready CRM operating system',
        ];
        $settingStmt = $pdo->prepare('INSERT INTO system_settings (company_id, setting_key, setting_value, updated_at) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=VALUES(updated_at)');
        foreach ($settings as $key => $value) {
            $settingStmt->execute([$companyId, $key, $value]);
        }

        $featureSeed = json_decode(file_get_contents(__DIR__ . '/database/feature_seed.json'), true);
        $featureStmt = $pdo->prepare('INSERT INTO feature_registry (category_name, feature_name, source_module, usage_summary, created_at) VALUES (?,?,?,?,NOW())');
        foreach ($featureSeed as $row) {
            if (empty($row['category_name']) || empty($row['feature_name']) || empty($row['source_module'])) {
                continue;
            }
            $featureStmt->execute([$row['category_name'], $row['feature_name'], $row['source_module'], $row['usage_summary'] ?? null]);
        }

        $seedSubs = $pdo->prepare('INSERT INTO subscriptions (company_id, plan_name, billing_cycle, subscription_status, seat_count, monthly_amount, renewal_date, trial_ends_at, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $seedSubs->execute([$companyId, 'Growth', 'Monthly', 'Trial', 5, 299, date('Y-m-d', strtotime('+30 days')), date('Y-m-d', strtotime('+14 days')), 'Starter subscription seeded during install.']);

        $seedInvoice = $pdo->prepare('INSERT INTO billing_invoices (company_id, invoice_number, plan_name, amount, invoice_status, due_date, paid_at, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
        $seedInvoice->execute([$companyId, 'INV-' . date('Ymd') . '-001', 'Growth', 299, 'Pending', date('Y-m-d', strtotime('+14 days')), null, 'Starter invoice seeded during install.']);

        $onboardingSeed = [
            ['Foundation','install_complete','Complete system install','admin',1,1],
            ['Branding','review_branding','Review branding and support settings','admin',2,0],
            ['Communications','configure_email','Configure email provider','admin',3,0],
            ['Communications','configure_sms','Configure SMS provider','admin',4,0],
            ['Security','review_permissions','Review role permissions and team access','admin',5,0],
            ['Operations','seed_first_team','Create managers and agents','admin',6,0],
            ['Go Live','run_worker','Run background worker / cron','admin',7,0],
            ['Go Live','import_clients','Import or create first clients and leads','manager',8,0],
        ];
        $seedChecklist = $pdo->prepare('INSERT INTO onboarding_items (company_id, category_name, item_key, item_label, owner_role, is_complete, completed_at, sort_order, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
        foreach ($onboardingSeed as [$category,$key,$label,$role,$sort,$complete]) {
            $seedChecklist->execute([$companyId, $category, $key, $label, $role, $complete, $complete ? date('Y-m-d H:i:s') : null, $sort]);
        }

        if ($seedDemo) {
            $demoSql = file_get_contents(__DIR__ . '/database/demo_seed.sql');
            $demoSql = str_replace(['__COMPANY_ID__', '__ADMIN_USER_ID__'], [(string) $companyId, (string) $adminId], $demoSql);
            $pdo->exec($demoSql);
        }

        $config = "<?php\nreturn [\n    'host' => '" . addslashes($host) . "',\n    'port' => '" . addslashes($port) . "',\n    'database' => '" . addslashes($database) . "',\n    'username' => '" . addslashes($username) . "',\n    'password' => '" . addslashes($password) . "',\n];\n";
        file_put_contents(__DIR__ . '/config/database.php', $config);
        $success = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Install CS One CRM Phase 7</title><link rel="stylesheet" href="public/assets/css/app.css"></head><body><main class="content content--full"><div class="card" style="max-width:820px;width:100%"><h2>Install CS One CRM Phase 7</h2><p class="muted">Fresh install for the commercial-ready CRM shell with branding, subscription records, onboarding checklists, and deployment tooling.</p><?php if ($error): ?><div class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?><?php if ($success): ?><p>Installation completed.</p><p><a href="public/index.php?page=login">Go to login</a></p><?php else: ?><form method="post" class="stack-form"><input name="host" value="localhost" placeholder="DB Host"><input name="port" value="3306" placeholder="DB Port"><input name="database" required placeholder="Database"><input name="username" required placeholder="DB Username"><input type="password" name="password" placeholder="DB Password"><input name="tenant_name" value="Default Tenant" placeholder="Tenant / Company Name"><input name="tenant_key" value="default-tenant" placeholder="Tenant Key"><input name="app_name" value="CS One CRM Phase 7" placeholder="Application Name"><input name="brand_support_email" value="support@example.com" placeholder="Brand Support Email"><input type="color" name="accent_color" value="#0f62fe"><input name="email_from_address" value="noreply@example.com" placeholder="Default From Address"><input name="admin_name" required placeholder="Admin Name"><input type="email" name="admin_email" required placeholder="Admin Email"><input type="password" name="admin_password" required placeholder="Admin Password"><label><input type="checkbox" name="seed_demo" value="1" checked> Seed demo data</label><button type="submit">Install System</button></form><?php endif; ?></div></main></body></html>
