<div class="page-header"><div><h2>Maintenance Center</h2><p>Manage retention policy, cleanup runs, and configuration snapshots.</p></div></div>
<div class="grid cols-2">
  <div class="card">
    <h3>Retention settings</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <label>Audit log retention (days)<input type="number" name="retention_audit_days" value="<?= e((string)$retention['audit']) ?>"></label>
      <label>API log retention (days)<input type="number" name="retention_api_days" value="<?= e((string)$retention['api']) ?>"></label>
      <label>Webhook retention (days)<input type="number" name="retention_webhook_days" value="<?= e((string)$retention['webhooks']) ?>"></label>
      <label>Outbound message retention (days)<input type="number" name="retention_outbound_days" value="<?= e((string)$retention['outbound']) ?>"></label>
      <button class="btn" type="submit">Save settings</button>
    </form>
  </div>
  <div class="card">
    <h3>Run maintenance</h3>
    <form method="post" style="margin-bottom:12px;"><?= csrf_field() ?><input type="hidden" name="action" value="cleanup"><button class="btn" type="submit">Run cleanup now</button></form>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="snapshot"><button class="btn btn-secondary" type="submit">Create config snapshot</button></form>
    <p class="muted">Snapshots are written to <code>storage/snapshots</code>.</p>
  </div>
</div>
<div class="card"><h3>Recent maintenance runs</h3><table><thead><tr><th>Type</th><th>Result</th><th>Created</th></tr></thead><tbody><?php foreach ($runs as $run): ?><tr><td><?= e($run['run_type']) ?></td><td><code><?= e($run['result_json']) ?></code></td><td><?= e($run['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div>
