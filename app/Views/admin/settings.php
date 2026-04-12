<div class="page-header"><div><h2>System Settings</h2><p>Centralized configuration for app identity, tenancy, providers, API, and worker behavior.</p></div></div>
<div class="card">
<form method="post" class="stack-form">
<?= csrf_field() ?>
<input name="app_name" value="<?= e($settingsMap['app_name'] ?? 'CS One CRM') ?>" placeholder="Application Name">
<input name="default_timezone" value="<?= e($settingsMap['default_timezone'] ?? 'America/Chicago') ?>" placeholder="Timezone">
<input name="tenant_mode" value="<?= e($settingsMap['tenant_mode'] ?? 'tenant-isolated') ?>" placeholder="Tenant Mode">
<input name="email_provider" value="<?= e($settingsMap['email_provider'] ?? '') ?>" placeholder="Email Provider (PHP Mail / SendGrid / Mailgun)">
<input name="email_from_address" value="<?= e($settingsMap['email_from_address'] ?? '') ?>" placeholder="From Address">
<input name="email_api_key" value="<?= e($settingsMap['email_api_key'] ?? '') ?>" placeholder="Email API Key">
<input name="email_domain" value="<?= e($settingsMap['email_domain'] ?? '') ?>" placeholder="Mailgun Domain (optional)">
<input name="sms_provider" value="<?= e($settingsMap['sms_provider'] ?? '') ?>" placeholder="SMS Provider (Twilio)">
<input name="sms_account_sid" value="<?= e($settingsMap['sms_account_sid'] ?? '') ?>" placeholder="SMS Account SID">
<input name="sms_auth_token" value="<?= e($settingsMap['sms_auth_token'] ?? '') ?>" placeholder="SMS Auth Token">
<input name="sms_from_number" value="<?= e($settingsMap['sms_from_number'] ?? '') ?>" placeholder="SMS Number">
<input name="ai_provider" value="<?= e($settingsMap['ai_provider'] ?? 'heuristic') ?>" placeholder="AI Provider">
<input name="ai_api_key" value="<?= e($settingsMap['ai_api_key'] ?? '') ?>" placeholder="AI API Key / optional hook">
<input name="api_token" value="<?= e($settingsMap['api_token'] ?? 'change-me') ?>" placeholder="API Token">
<select name="stripe_mode"><option value="test" <?= (($settingsMap['stripe_mode'] ?? 'test')==='test')?'selected':''; ?>>Stripe Test</option><option value="live" <?= (($settingsMap['stripe_mode'] ?? '')==='live')?'selected':''; ?>>Stripe Live</option></select><input name="stripe_public_key" placeholder="Stripe Public Key" value="<?= e($settingsMap['stripe_public_key'] ?? '') ?>"><input name="stripe_secret_key" placeholder="Stripe Secret Key" value="<?= e($settingsMap['stripe_secret_key'] ?? '') ?>"><input name="stripe_webhook_secret" placeholder="Stripe Webhook Secret" value="<?= e($settingsMap['stripe_webhook_secret'] ?? '') ?>"><input name="billing_checkout_success_url" placeholder="Billing Success URL" value="<?= e($settingsMap['billing_checkout_success_url'] ?? '') ?>"><input name="billing_checkout_cancel_url" placeholder="Billing Cancel URL" value="<?= e($settingsMap['billing_checkout_cancel_url'] ?? '') ?>"><input name="worker_batch_size" value="<?= e($settingsMap['worker_batch_size'] ?? '25') ?>" placeholder="Worker Batch Size">
<button type="submit">Save Settings</button>
</form>
</div>
<div class="card subtle"><h3>Worker command</h3><div class="code-block">php cron/worker.php 1</div><p class="muted">Run this from cron to process workflow jobs and outbound communications for a tenant.</p></div>
