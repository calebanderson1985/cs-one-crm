<?php
use App\Core\Auth;
$currentUser = Auth::user();
$page = $_GET['page'] ?? 'dashboard';
$success = flash('success');
$error = flash('error');
$appName = $GLOBALS['pdo'] instanceof PDO ? setting($GLOBALS['pdo'], 'app_name', 'CS One CRM Phase 16') : 'CS One CRM Phase 16';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? $appName)) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="shell">
    <?php if ($currentUser): ?>
    <aside class="sidebar">
        <h1><?= e($appName) ?></h1>
        <?php $unreadNotifications = $currentUser ? (new App\Models\Notification($GLOBALS['pdo']))->unreadCount() : 0; ?>
        <div class="meta"><?= e($currentUser['full_name']) ?> · <?= e($currentUser['role']) ?><?php if ($unreadNotifications): ?> · <?= e((string)$unreadNotifications) ?> unread<?php endif; ?></div>
        <nav>
            <?php if (Auth::canAccess('dashboard')): ?><a class="<?= active_nav($page, 'dashboard') ?>" href="index.php">Dashboard</a><?php endif; ?>
            <div class="nav-group">CRM Core</div>
            <?php if (Auth::canAccess('clients')): ?><a class="<?= active_nav($page, 'clients') ?>" href="index.php?page=clients">Clients</a><?php endif; ?>
            <?php if (Auth::canAccess('leads')): ?><a class="<?= active_nav($page, 'leads') ?>" href="index.php?page=leads">Leads</a><?php endif; ?>
            <?php if (Auth::canAccess('deals')): ?><a class="<?= active_nav($page, 'deals') ?>" href="index.php?page=deals">Deals</a><?php endif; ?>
            <?php if (Auth::canAccess('tasks')): ?><a class="<?= active_nav($page, 'tasks') ?>" href="index.php?page=tasks">Tasks</a><?php endif; ?>
            <div class="nav-group">Communication</div>
            <?php if (Auth::canAccess('communications')): ?><a class="<?= active_nav($page, 'communications') ?>" href="index.php?page=communications">Email / SMS</a><?php endif; ?>
            <?php if (Auth::canAccess('notifications')): ?><a class="<?= active_nav($page, 'notifications') ?>" href="index.php?page=notifications">Notifications</a><?php endif; ?>
            <?php if (Auth::canAccess('documents')): ?><a class="<?= active_nav($page, 'documents') ?>" href="index.php?page=documents">Documents</a><?php endif; ?>
            <div class="nav-group">Commissions & Finance</div>
            <?php if (Auth::canAccess('commissions')): ?><a class="<?= active_nav($page, 'commissions') ?>" href="index.php?page=commissions">Commissions</a><?php endif; ?>
            <div class="nav-group">Reporting & Automation</div>
            <?php if (Auth::canAccess('reports')): ?><a class="<?= active_nav($page, 'reports') ?>" href="index.php?page=reports">Reports</a><?php endif; ?>
            <?php if (Auth::canAccess('workflows')): ?><a class="<?= active_nav($page, 'workflows') ?>" href="index.php?page=workflows">Workflows</a><?php endif; ?>
            <div class="nav-group">AI & Intelligence</div>
            <?php if (Auth::canAccess('ai')): ?><a class="<?= active_nav($page, 'ai') ?>" href="index.php?page=ai">AI Workspace</a><?php endif; ?>
            <div class="nav-group">Portals</div>
            <?php if (Auth::canAccess('portals')): ?><a class="<?= active_nav($page, 'portals') ?>" href="index.php?page=portals">Portal Center</a><?php endif; ?>
            <div class="nav-group">Administration</div>
            <?php if (Auth::canAccess('users')): ?><a class="<?= active_nav($page, 'users') ?>" href="index.php?page=users">User Management</a><?php endif; ?>
            <?php if (Auth::canAccess('permissions')): ?><a class="<?= active_nav($page, 'permissions') ?>" href="index.php?page=permissions">Permissions</a><?php endif; ?>
            <?php if (Auth::canAccess('audit')): ?><a class="<?= active_nav($page, 'audit') ?>" href="index.php?page=audit">Audit Trail</a><?php endif; ?>
            <?php if (Auth::canAccess('api')): ?><a class="<?= active_nav($page, 'api') ?>" href="index.php?page=api">API</a><?php endif; ?>
            <?php if (Auth::canAccess('tokens')): ?><a class="<?= active_nav($page, 'tokens') ?>" href="index.php?page=tokens">API Tokens</a><?php endif; ?>
            <?php if (Auth::canAccess('api_analytics')): ?><a class="<?= active_nav($page, 'api_analytics') ?>" href="index.php?page=api_analytics">API Analytics</a><?php endif; ?>
            <?php if (Auth::canAccess('queue_ops')): ?><a class="<?= active_nav($page, 'queue_ops') ?>" href="index.php?page=queue_ops">Queue Operations</a><?php endif; ?>
            <?php if (Auth::canAccess('webhooks')): ?><a class="<?= active_nav($page, 'webhooks') ?>" href="index.php?page=webhooks">Webhook Events</a><?php endif; ?>
            <?php if (Auth::canAccess('diagnostics')): ?><a class="<?= active_nav($page, 'diagnostics') ?>" href="index.php?page=diagnostics">Diagnostics</a><?php endif; ?>
            <?php if (Auth::canAccess('ops_console')): ?><a class="<?= active_nav($page, 'ops_console') ?>" href="index.php?page=ops_console">Ops Console</a><?php endif; ?>
            <?php if (Auth::canAccess('announcements')): ?><a class="<?= active_nav($page, 'announcements') ?>" href="index.php?page=announcements">Announcements</a><?php endif; ?>
            <?php if (Auth::canAccess('maintenance')): ?><a class="<?= active_nav($page, 'maintenance') ?>" href="index.php?page=maintenance">Maintenance Center</a><?php endif; ?>
            <?php if (Auth::canAccess('support')): ?><a class="<?= active_nav($page, 'support') ?>" href="index.php?page=support">Support Center</a><?php endif; ?>
            <?php if (Auth::canAccess('sla')): ?><a class="<?= active_nav($page, 'sla') ?>" href="index.php?page=sla">SLA Policies</a><?php endif; ?>
            <?php if (Auth::canAccess('knowledge_base')): ?><a class="<?= active_nav($page, 'knowledge_base') ?>" href="index.php?page=knowledge_base"><?= current_user_role() === 'client' ? 'Help Center' : 'Knowledge Base' ?></a><?php endif; ?>
            <?php if (Auth::canAccess('company_switch') && is_super_admin()): ?><a class="<?= active_nav($page, 'company_switch') ?>" href="index.php?page=company_switch">Company Switch</a><?php endif; ?>
            <?php if (Auth::canAccess('onboarding')): ?><a class="<?= active_nav($page, 'onboarding') ?>" href="index.php?page=onboarding">Launch Wizard</a><?php endif; ?>
            <?php if (Auth::canAccess('features')): ?><a class="<?= active_nav($page, 'features') ?>" href="index.php?page=features">Feature Registry</a><?php endif; ?>
            <?php if (Auth::canAccess('settings')): ?><a class="<?= active_nav($page, 'settings') ?>" href="index.php?page=settings">System Settings</a><?php endif; ?>
            <a href="index.php?page=logout">Logout</a>
        </nav>
    </aside>
    <?php endif; ?>
    <main class="content <?php if (!$currentUser) echo 'content--full'; ?>">
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
        <?php include $viewFile; ?>
    </main>
</div>
</body>
</html>
