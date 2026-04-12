<div class="page-header"><div><h2>Branding Center</h2><p>White-label the CRM shell with application naming, support details, login messaging, and visual accents.</p></div></div>
<div class="grid-two">
  <div class="card">
    <h3>Brand Settings</h3>
    <form method="post" class="stack-form">
      <?= csrf_field() ?>
      <input name="app_name" placeholder="Application Name" value="<?= e($settingsMap['app_name'] ?? 'CS One CRM Phase 7') ?>" required>
      <input name="brand_support_email" type="email" placeholder="Support Email" value="<?= e($settingsMap['brand_support_email'] ?? '') ?>">
      <input name="company_tagline" placeholder="Tagline" value="<?= e($settingsMap['company_tagline'] ?? '') ?>">
      <input name="login_headline" placeholder="Login Headline" value="<?= e($settingsMap['login_headline'] ?? 'All in one CRM for growth teams') ?>">
      <input name="accent_color" type="color" value="<?= e($settingsMap['accent_color'] ?? '#0f62fe') ?>">
      <textarea name="footer_branding" placeholder="Footer Branding"><?= e($settingsMap['footer_branding'] ?? 'Powered by CS One CRM') ?></textarea>
      <div class="actions"><button type="submit">Save Branding</button></div>
    </form>
  </div>
  <div class="card">
    <h3>Preview</h3>
    <div class="preview-shell">
      <div class="preview-top" style="background: <?= e($settingsMap['accent_color'] ?? '#0f62fe') ?>"></div>
      <div class="preview-body">
        <h1><?= e($settingsMap['app_name'] ?? 'CS One CRM Phase 7') ?></h1>
        <p class="muted"><?= e($settingsMap['company_tagline'] ?? 'Commercial-ready CRM platform') ?></p>
        <div class="note"><?= e($settingsMap['login_headline'] ?? 'All in one CRM for growth teams') ?></div>
        <p style="margin-top:16px"><strong>Support:</strong> <?= e($settingsMap['brand_support_email'] ?? 'support@example.com') ?></p>
      </div>
    </div>
    <p class="muted" style="margin-top:12px">Logo and favicon uploads are planned for the next phase. Phase 7 focuses on white-label text, accenting, and login polish.</p>
  </div>
</div>
