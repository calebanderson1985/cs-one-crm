<div class="page-header"><div><h2>Notification Center</h2><p>System alerts, workflow notices, reminders, exports, password events, and operational updates.</p></div>
<form method="post" class="actions"><?= csrf_field() ?><input type="hidden" name="action" value="mark_all_read"><button type="submit" class="btn-muted">Mark All Read</button></form>
</div>
<div class="card">
<table>
<thead><tr><th>Title</th><th>Level</th><th>Message</th><th>Created</th><th></th></tr></thead>
<tbody>
<?php foreach ($notifications as $row): ?>
<tr>
<td><?= e($row['title']) ?><?php if (empty($row['is_read'])): ?> <span class="tag">Unread</span><?php endif; ?></td>
<td><span class="tag"><?= e($row['level_name']) ?></span></td>
<td><?= e($row['message_text']) ?><?php if (!empty($row['link_url'])): ?><div><a href="<?= e($row['link_url']) ?>">Open</a></div><?php endif; ?></td>
<td><?= e($row['created_at']) ?></td>
<td class="table-actions"><?php if (empty($row['is_read'])): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?= e((string)$row['id']) ?>"><button type="submit">Mark Read</button></form><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
