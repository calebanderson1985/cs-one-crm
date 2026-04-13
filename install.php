<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

$error = null;
$success = false;

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $stmt->execute([$column]);
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function normalize_feature(array $row): array
{
    return [
        'category_name' => trim((string) ($row['category_name'] ?? $row['category'] ?? 'General')),
        'feature_name' => trim((string) ($row['feature_name'] ?? $row['module_name'] ?? $row['name'] ?? 'Unnamed Feature')),
        'source_module' => trim((string) ($row['source_module'] ?? $row['module_key'] ?? $row['module'] ?? 'system')),
        'usage_summary' => trim((string) ($row['usage_summary'] ?? $row['description'] ?? $row['summary'] ?? '')),
    ];
}

function repair_schema(PDO $pdo): void
{
    if (table_exists($pdo, 'onboarding_steps') && !column_exists($pdo, 'onboarding_steps', 'action_url')) {
        $pdo->exec("ALTER TABLE onboarding_steps ADD COLUMN action_url VARCHAR(255) NULL AFTER description_text");
    }

    if (!table_exists($pdo, 'login_attempts')) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NULL,
            user_id INT NULL,
            email VARCHAR(190) NOT NULL,
            ip_address VARCHAR(45) NULL,
            success_flag TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX idx_login_attempts_email (email),
            INDEX idx_login_attempts_ip (ip_address),
            INDEX idx_login_attempts_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

function seed_feature_categories(PDO $pdo): void
{
    if (!table_exists($pdo, 'feature_categories')) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS feature_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_key VARCHAR(120) NOT NULL,
            category_name VARCHAR(190) NOT NULL,
            description TEXT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_feature_categories_key (category_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } else {
        if (!column_exists($pdo, 'feature_categories', 'category_name')) {
            $pdo->exec("ALTER TABLE feature_categories ADD COLUMN category_name VARCHAR(190) NOT NULL AFTER category_key");
        }
        if (!column_exists($pdo, 'feature_categories', 'description')) {
            $pdo->exec("ALTER TABLE feature_categories ADD COLUMN description TEXT NULL AFTER category_name");
        }
        if (!column_exists($pdo, 'feature_categories', 'sort_order')) {
            $pdo->exec("ALTER TABLE feature_categories ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER description");
        }
        if (!column_exists($pdo, 'feature_categories', 'updated_at')) {
            $pdo->exec("ALTER TABLE feature_categories ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_at");
        }
    }

    $categories = [
        ['category_key' => 'crm_core', 'category_name' => 'CRM Core', 'description' => 'Clients, leads, deals, tasks, and core CRM records.', 'sort_order' => 10],
        ['category_key' => 'communication', 'category_name' => 'Communication', 'description' => 'Email, SMS, ticket replies, and engagement records.', 'sort_order' => 20],
        ['category_key' => 'commissions', 'category_name' => 'Commissions & Finance', 'description' => 'Commission tracking, payouts, invoices, and billing.', 'sort_order' => 30],
        ['category_key' => 'reporting', 'category_name' => 'Reporting & Analytics', 'description' => 'Dashboards, exports, KPIs, and analytics.', 'sort_order' => 40],
        ['category_key' => 'workflows', 'category_name' => 'Workflows & Automation', 'description' => 'Workflow rules, queues, and automation execution.', 'sort_order' => 50],
        ['category_key' => 'support', 'category_name' => 'Support & Service', 'description' => 'Tickets, conversations, SLA policies, and knowledge base.', 'sort_order' => 60],
        ['category_key' => 'platform', 'category_name' => 'Platform & Administration', 'description' => 'Users, settings, API, tenant controls, and operations.', 'sort_order' => 70],
    ];

    $stmt = $pdo->prepare("INSERT INTO feature_categories (
        category_key, category_name, description, sort_order, created_at, updated_at
    ) VALUES (
        :category_key, :category_name, :description, :sort_order, NOW(), NOW()
    ) ON DUPLICATE KEY UPDATE
        category_name = VALUES(category_name),
        description = VALUES(description),
        sort_order = VALUES(sort_order),
        updated_at = NOW()");

    foreach ($categories as $category) {
        $stmt->execute([
            ':category_key' => $category['category_key'],
            ':category_name' => $category['category_name'],
            ':description' => $category['description'],
            ':sort_order' => $category['sort_order'],
        ]);
    }
}

function seed_feature_registry(PDO $pdo, string $seedPath): void
{
    if (!table_exists($pdo, 'feature_registry')) {
        return;
    }

    $hasCategoryName = column_exists($pdo, 'feature_registry', 'category_name');
    $seed = is_file($seedPath) ? json_decode((string) file_get_contents($seedPath), true) : [];
    if (!is_array($seed)) {
        return;
    }

    if ($hasCategoryName) {
        $stmt = $pdo->prepare('INSERT INTO feature_registry (category_name, feature_name, source_module, usage_summary, created_at) VALUES (?,?,?,?,NOW())');
    } else {
        $stmt = $pdo->prepare('INSERT INTO feature_registry (feature_name, source_module, usage_summary, created_at) VALUES (?,?,?,NOW())');
    }

    foreach ($seed as $row) {
        if (!is_array($row)) {
            continue;
        }
        $feature = normalize_feature($row);
        if ($hasCategoryName) {
            $stmt->execute([$feature['category_name'], $feature['feature_name'], $feature['source_module'], $feature['usage_summary']]);
        } else {
            $stmt->execute([$feature['feature_name'], $feature['source_module'], $feature['usage_summary']]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim((string) ($_POST['host'] ?? 'localhost'));
    $port = trim((string) ($_POST['port'] ?? '3306'));
    $database = trim((string) ($_POST['database'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $adminName = trim((string) ($_POST['admin_name'] ?? 'Administrator'));
    $adminEmail = trim((string) ($_POST['admin_email'] ?? 'admin@example.com'));
    $adminPassword = (string) ($_POST['admin_password'] ?? '');
    $tenantName = trim((string) ($_POST['tenant_name'] ?? 'Default Tenant'));
    $tenantKey = trim((string) ($_POST['tenant_key'] ?? 'default-tenant'));
    $appName = trim((string) ($_POST['app_name'] ?? 'CS One CRM Phase 18'));
    $emailFrom = trim((string) ($_POST['email_from_address'] ?? 'noreply@example.com'));
    $seedDemo = !empty($_POST['seed_demo']);

    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        if (is_file(__DIR__ . '/database/schema.sql')) {
            $pdo->exec((string) file_get_contents(__DIR__ . '/database/schema.sql'));
        }
        repair_schema($pdo);
        seed_feature_categories($pdo);

        $stmt = $pdo->prepare('INSERT INTO companies (company_name, tenant_key, status, created_at, updated_at) VALUES (?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE company_name=VALUES(company_name), updated_at=VALUES(updated_at)');
        $stmt->execute([$tenantName, $tenantKey, 'Active']);
        $companyIdStmt = $pdo->prepare('SELECT id FROM companies WHERE tenant_key = ? LIMIT 1');
        $companyIdStmt->execute([$tenantKey]);
        $companyId = (int) ($companyIdStmt->fetchColumn() ?: 0);

        $adminStmt = $pdo->prepare('SELECT id FROM users WHERE company_id = ? AND email = ? LIMIT 1');
        $adminStmt->execute([$companyId, $adminEmail]);
        $adminId = (int) ($adminStmt->fetchColumn() ?: 0);
        if ($adminId > 0) {
            $update = $pdo->prepare('UPDATE users SET full_name=?, password_hash=?, role=\'admin\', is_active=1, updated_at=NOW() WHERE id=?');
            $update->execute([$adminName, password_hash($adminPassword, PASSWORD_DEFAULT), $adminId]);
        } else {
            $insert = $pdo->prepare('INSERT INTO users (company_id, full_name, email, password_hash, role, manager_user_id, portal_client_id, is_active, created_at, updated_at) VALUES (?,?,?,?,\'admin\',NULL,NULL,1,NOW(),NOW())');
            $insert->execute([$companyId, $adminName, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT)]);
            $adminId = (int) $pdo->lastInsertId();
        }

        require_once __DIR__ . '/app/Services/PermissionService.php';
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
            'stripe_mode' => 'test',
            'stripe_public_key' => '',
            'stripe_secret_key' => '',
            'stripe_webhook_secret' => '',
            'billing_checkout_success_url' => '',
            'billing_checkout_cancel_url' => '',
            'support_ingest_token' => bin2hex(random_bytes(16)),
        ];
        $settingStmt = $pdo->prepare('INSERT INTO system_settings (company_id, setting_key, setting_value, updated_at) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=VALUES(updated_at)');
        foreach ($settings as $key => $value) {
            $settingStmt->execute([$companyId, $key, $value]);
        }

        seed_feature_registry($pdo, __DIR__ . '/database/feature_seed.json');

        if ($seedDemo && is_file(__DIR__ . '/database/demo_seed.sql')) {
            $demoSql = (string) file_get_contents(__DIR__ . '/database/demo_seed.sql');
            $demoSql = str_replace(['__COMPANY_ID__', '__ADMIN_USER_ID__'], [(string) $companyId, (string) $adminId], $demoSql);
            $pdo->exec($demoSql);
        }

        $config = "<?php\nreturn [\n    'host' => '" . addslashes($host) . "',\n    'port' => '" . addslashes($port) . "',\n    'database' => '" . addslashes($database) . "',\n    'username' => '" . addslashes($username) . "',\n    'password' => '" . addslashes($password) . "',\n];\n";
        @file_put_contents(__DIR__ . '/config/database.php', $config);
        $success = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install CS One CRM Phase 18</title>
    <link rel="stylesheet" href="public/assets/css/app.css">
</head>
<body>
    <main class="content content--full">
        <div class="card narrow">
            <h2>Install CS One CRM Phase 18</h2>
            <?php if ($error): ?>
                <div class="alert" style="white-space:pre-wrap"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <p>Installation completed.</p>
                <p><a href="public/index.php?page=login">Go to login</a></p>
            <?php else: ?>
                <form method="post" class="stack-form">
                    <input name="host" value="localhost" placeholder="DB Host">
                    <input name="port" value="3306" placeholder="DB Port">
                    <input name="database" required placeholder="Database">
                    <input name="username" required placeholder="DB Username">
                    <input type="password" name="password" placeholder="DB Password">
                    <input name="tenant_name" value="Default Tenant" placeholder="Tenant / Company Name">
                    <input name="tenant_key" value="default-tenant" placeholder="Tenant Key">
                    <input name="app_name" value="CS One CRM Phase 18" placeholder="Application Name">
                    <input name="email_from_address" value="noreply@example.com" placeholder="Default From Address">
                    <input name="admin_name" required placeholder="Admin Name">
                    <input type="email" name="admin_email" required placeholder="Admin Email">
                    <input type="password" name="admin_password" required placeholder="Admin Password">
                    <label><input type="checkbox" name="seed_demo" value="1" checked> Seed demo data</label>
                    <button type="submit">Install System</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
