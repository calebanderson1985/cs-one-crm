<div class="page-header"><div><h2>Ops Console</h2><p>Test communications, AI, and monitor worker heartbeat status.</p></div></div>
<div class="grid cols-2">
  <div class="card">
    <h3>Integration tests</h3>
    <form method="post" style="margin-bottom:10px;"><?= csrf_field() ?><input type="hidden" name="action" value="test_email"><label>Email recipient<input type="email" name="test_recipient" value="<?= e(current_user_email()) ?>"></label><button class="btn" type="submit">Queue test email</button></form>
    <form method="post" style="margin-bottom:10px;"><?= csrf_field() ?><input type="hidden" name="action" value="test_sms"><label>Phone number<input type="text" name="test_phone" value=""></label><button class="btn" type="submit">Queue test SMS</button></form>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="test_ai"><label>AI prompt<textarea name="ai_prompt" rows="5">Summarize current CRM operational readiness.</textarea></label><button class="btn btn-secondary" type="submit">Run AI test</button></form>
    <?php if (!empty($aiResult)): ?><h4>AI result</h4><pre><?= e($aiResult) ?></pre><?php endif; ?>
  </div>
  <div class="card">
    <h3>Worker heartbeat</h3>
    <table><thead><tr><th>Worker</th><th>Last heartbeat</th><th>Status</th><th>Payload</th></tr></thead><tbody>
      <?php foreach ($heartbeats as $row): $stale = (time() - strtotime((string)$row['heartbeat_at'])) > 600; ?>
      <tr>
        <td><?= e($row['worker_name']) ?></td>
        <td><?= e($row['heartbeat_at']) ?></td>
        <td><?= $stale ? 'STALE' : 'OK' ?> / <?= e($row['status_text']) ?></td>
        <td><code><?= e($row['payload_json']) ?></code></td>
      </tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
</div>
