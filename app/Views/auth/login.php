<?php
$title = 'Login';
$appName = $GLOBALS['pdo'] instanceof PDO ? setting($GLOBALS['pdo'], 'app_name', 'CS One CRM Phase 7') : 'CS One CRM Phase 7';
$headline = $GLOBALS['pdo'] instanceof PDO ? setting($GLOBALS['pdo'], 'login_headline', 'All in one CRM for growth teams') : 'All in one CRM for growth teams';
$tagline = $GLOBALS['pdo'] instanceof PDO ? setting($GLOBALS['pdo'], 'company_tagline', 'Unified CRM operating system') : 'Unified CRM operating system';
$support = $GLOBALS['pdo'] instanceof PDO ? setting($GLOBALS['pdo'], 'brand_support_email', 'support@example.com') : 'support@example.com';
?>
<div class="login-grid">
    <div class="card login-splash">
        <span class="tag">Phase 7</span>
        <h1><?= e($appName) ?></h1>
        <p><?= e($headline) ?></p>
        <div class="note"><?= e($tagline) ?></div>
        <ul>
            <li>CRM Core, workflows, reporting, portals, AI, and communications in one shell</li>
            <li>Commercial operations with branding, subscriptions, invoicing, and onboarding</li>
            <li>Role-aware access for Admin, Manager, Agent, and Client experiences</li>
        </ul>
        <p class="muted">Support: <?= e($support) ?></p>
    </div>
    <div class="card narrow">
        <h2>Login</h2>
        <?php if (!empty($error)): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="index.php?page=login" class="stack-form">
            <?= csrf_field() ?>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Sign In</button>
        </form><p class="muted" style="margin-top:12px"><a href="index.php?page=forgot_password">Forgot password?</a></p>
    </div>
</div>
