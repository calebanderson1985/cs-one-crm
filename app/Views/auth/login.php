<?php $title = 'Sign In'; ?>
<div class="login-shell">
    <div class="login-panel login-panel--brand">
        <div class="eyebrow">Business CRM Platform</div>
        <h1><?= e(setting($GLOBALS['pdo'], 'app_name', 'CS One CRM')) ?></h1>
        <p>Manage clients, communications, service tickets, workflows, reporting, and portals from one organized workspace.</p>
        <div class="login-points">
            <div>Structured CRM records and richer customer profiles</div>
            <div>Support portal, mailbox sync, and threaded conversations</div>
            <div>Operational dashboards, automation, and governance</div>
        </div>
    </div>
    <div class="login-panel login-panel--form">
        <div class="card login-card">
            <h2>Welcome back</h2>
            <p class="muted">Sign in to continue to your workspace.</p>
            <?php if (!empty($error)): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
            <form method="post" action="index.php?page=login" class="stack-form">
                <?= csrf_field() ?>
                <label>Email address</label>
                <input type="email" name="email" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <button type="submit">Sign In</button>
            </form>
            <p class="muted" style="margin-top:14px"><a href="index.php?page=forgot_password">Forgot password?</a></p>
        </div>
    </div>
</div>
