
<div class="page-header"><div><h2>Queue Operations</h2><p>Retry failed workflow jobs and outbound communications from one operational center.</p></div></div>
<div class="stats-grid">
  <div class="card stat"><span>Workflow Queued</span><strong><?= (int)$summary['workflow_queued'] ?></strong></div>
  <div class="card stat"><span>Workflow Failed</span><strong><?= (int)$summary['workflow_failed'] ?></strong></div>
  <div class="card stat"><span>Outbound Queued</span><strong><?= (int)$summary['outbound_queued'] ?></strong></div>
  <div class="card stat"><span>Outbound Failed</span><strong><?= (int)$summary['outbound_failed'] ?></strong></div>
</div>
<div class="grid-two">
<div class="card"><h3>Failed Workflow Jobs</h3><table><thead><tr><th>Workflow</th><th>Error</th><th>Updated</th><th></th></tr></thead><tbody><?php foreach ($workflowFailed as $row): ?><tr><td><?= e($row['workflow_name'] ?? 'Workflow') ?></td><td><?= e($row['error_text'] ?? '') ?></td><td><?= e($row['updated_at'] ?? $row['created_at']) ?></td><td><?php if (App\Core\Auth::can('queue_ops','edit')): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="retry_workflow"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button type="submit" class="btn-muted">Retry</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<div class="card"><h3>Failed Outbound Messages</h3><table><thead><tr><th>Channel</th><th>Recipient</th><th>Error</th><th></th></tr></thead><tbody><?php foreach ($outboundFailed as $row): ?><tr><td><?= e($row['channel']) ?></td><td><?= e($row['recipient']) ?></td><td><?= e($row['error_text'] ?? '') ?></td><td><?php if (App\Core\Auth::can('queue_ops','edit')): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="retry_outbound"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button type="submit" class="btn-muted">Retry</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
