<?php
$editing = !empty($editMessage);
$editingTemplate = !empty($editTemplate);
$canCreate = App\Core\Auth::can('communications','create');
$canEdit = App\Core\Auth::can('communications','edit');
$canDelete = App\Core\Auth::can('communications','delete');
?>
<div class="page-header"><div><h2>Communication Center</h2><p>Email, SMS, templates, provider queues, and communication history in one categorized hub.</p></div><div class="actions"><?php if ($canEdit): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="process_queue"><button type="submit" class="btn-muted">Process Queue</button></form><?php endif; ?></div></div>
<div class="grid-two">
<div>
<?php if ($canCreate || ($editing && $canEdit)): ?>
<div class="card">
<h3><?= $editing ? 'Edit Communication Log' : 'Send / Queue Message' ?></h3>
<form method="post" class="stack-form">
<?= csrf_field() ?>
<input type="hidden" name="action" value="<?= $editing ? 'update' : 'send' ?>">
<?php if ($editing): ?><input type="hidden" name="id" value="<?= e((string)$editMessage['id']) ?>"><?php endif; ?>
<select name="channel"><?php foreach (['Email','SMS'] as $v): ?><option value="<?= e($v) ?>" <?= (($editMessage['channel'] ?? 'Email')===$v)?'selected':''; ?>><?= e($v) ?></option><?php endforeach; ?></select>
<select name="template_id"><option value="">Template (optional)</option><?php foreach ($templates as $template): ?><option value="<?= e((string)$template['id']) ?>" <?= ((string)($editMessage['template_id'] ?? '') === (string)$template['id']) ? 'selected' : '' ?>><?= e($template['template_name']) ?> · <?= e($template['channel']) ?></option><?php endforeach; ?></select>
<select name="related_type"><option value="">Related Module</option><?php foreach (['Client','Lead','Deal','Task'] as $type): ?><option value="<?= e($type) ?>" <?= (($editMessage['related_type'] ?? '') === $type)?'selected':''; ?>><?= e($type) ?></option><?php endforeach; ?></select>
<input name="related_id" placeholder="Related Record ID" value="<?= e((string)($editMessage['related_id'] ?? '')) ?>">
<input name="recipient" placeholder="Recipient email or phone" value="<?= e($editMessage['recipient'] ?? '') ?>" required>
<input name="subject_line" placeholder="Subject" value="<?= e($editMessage['subject_line'] ?? '') ?>">
<?php if (!$editing): ?><select name="delivery_mode"><option value="queue">Queue for worker</option><option value="send_now">Attempt send now</option></select><?php endif; ?>
<textarea name="body_text" placeholder="Message body"><?= e($editMessage['body_text'] ?? '') ?></textarea>
<div class="actions"><button type="submit"><?= $editing ? 'Save Changes' : 'Send / Queue' ?></button><?php if ($editing): ?><a class="btn-muted" href="index.php?page=communications">Cancel</a><?php endif; ?></div>
</form>
</div>
<?php endif; ?>
<?php if ($canCreate): ?>
<div class="card">
<h3>Inbound Log</h3>
<form method="post" class="stack-form">
<?= csrf_field() ?>
<input type="hidden" name="action" value="log_inbound">
<select name="channel"><?php foreach (['Email','SMS','Call','Portal'] as $v): ?><option value="<?= e($v) ?>"><?= e($v) ?></option><?php endforeach; ?></select>
<select name="related_type"><option value="">Related Module</option><?php foreach (['Client','Lead','Deal','Task'] as $type): ?><option value="<?= e($type) ?>"><?= e($type) ?></option><?php endforeach; ?></select>
<input name="related_id" placeholder="Related Record ID">
<input name="recipient" placeholder="Sender / recipient" required>
<input name="subject_line" placeholder="Subject">
<input name="provider_name" placeholder="Provider / gateway">
<textarea name="body_text" placeholder="Inbound notes or body"></textarea>
<button type="submit">Log Inbound Message</button>
</form>
</div>
<div class="card">
<h3><?= $editingTemplate ? 'Edit Template' : 'Add Template' ?></h3>
<form method="post" class="stack-form">
<?= csrf_field() ?>
<input type="hidden" name="action" value="<?= $editingTemplate ? 'update_template' : 'create_template' ?>">
<?php if ($editingTemplate): ?><input type="hidden" name="id" value="<?= e((string)$editTemplate['id']) ?>"><?php endif; ?>
<input name="template_name" placeholder="Template Name" value="<?= e($editTemplate['template_name'] ?? '') ?>" required>
<select name="channel"><?php foreach (['Email','SMS'] as $v): ?><option value="<?= e($v) ?>" <?= (($editTemplate['channel'] ?? 'Email')===$v)?'selected':''; ?>><?= e($v) ?></option><?php endforeach; ?></select>
<input name="subject_template" placeholder="Subject template" value="<?= e($editTemplate['subject_template'] ?? '') ?>">
<select name="status"><?php foreach (['Active','Inactive'] as $v): ?><option value="<?= e($v) ?>" <?= (($editTemplate['status'] ?? 'Active')===$v)?'selected':''; ?>><?= e($v) ?></option><?php endforeach; ?></select>
<textarea name="body_template" placeholder="Use tokens like {{record.lead_name}} or {{record.company_name}}">
<?= e($editTemplate['body_template'] ?? '') ?></textarea>
<div class="actions"><button type="submit"><?= $editingTemplate ? 'Save Template' : 'Create Template' ?></button><?php if ($editingTemplate): ?><a class="btn-muted" href="index.php?page=communications">Cancel</a><?php endif; ?></div>
</form>
</div>
<?php endif; ?>
</div>
<div>
<div class="card"><h3>Communication Activity</h3><table><thead><tr><th>Channel</th><th>Recipient</th><th>Status</th><th>Provider</th><th></th></tr></thead><tbody><?php foreach ($messages as $row): ?><tr><td><?= e($row['channel']) ?><div class="muted"><?= e($row['direction']) ?></div></td><td><?= e($row['recipient']) ?><div class="muted"><?= e($row['subject_line']) ?></div></td><td><span class="tag"><?= e($row['status']) ?></span></td><td><?= e($row['provider_name']) ?></td><td class="table-actions"><?php if ($canEdit): ?><a href="index.php?page=communications&id=<?= e((string)$row['id']) ?>">Edit</a><?php endif; ?><?php if ($canDelete): ?><form method="post" onsubmit="return confirm('Delete this record?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e((string)$row['id']) ?>"><button class="btn-danger" type="submit">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<div class="card"><h3>Outbound Queue</h3><table><thead><tr><th>Channel</th><th>Recipient</th><th>Status</th><th>Provider</th></tr></thead><tbody><?php foreach (array_slice($outboundQueue,0,20) as $row): ?><tr><td><?= e($row['channel']) ?></td><td><?= e($row['recipient']) ?></td><td><span class="tag"><?= e($row['send_status']) ?></span></td><td><?= e($row['provider_name']) ?><div class="muted"><?= e((string)($row['error_text'] ?? '')) ?></div></td></tr><?php endforeach; ?></tbody></table></div>
<div class="card"><h3>Templates</h3><table><thead><tr><th>Name</th><th>Channel</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($templates as $row): ?><tr><td><?= e($row['template_name']) ?></td><td><?= e($row['channel']) ?></td><td><span class="tag"><?= e($row['status']) ?></span></td><td class="table-actions"><?php if ($canEdit): ?><a href="index.php?page=communications&template_id=<?= e((string)$row['id']) ?>">Edit</a><?php endif; ?><?php if ($canDelete): ?><form method="post" onsubmit="return confirm('Delete this template?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete_template"><input type="hidden" name="id" value="<?= e((string)$row['id']) ?>"><button class="btn-danger" type="submit">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
</div>
