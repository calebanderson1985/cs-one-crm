<?php
$canCreateDocuments = App\Core\Auth::can('documents', 'create');
$canDeleteDocuments = App\Core\Auth::can('documents', 'delete');
?>
<div class="page-header"><div><h2>Documents</h2><p>Centralized and permission-aware file storage for CRM records, statements, and client portal assets.</p></div></div>
<div class="grid-two">
<?php if ($canCreateDocuments): ?>
<div class="card">
<h3>Upload Document</h3>
<form method="post" enctype="multipart/form-data" class="stack-form">
<?= csrf_field() ?>
<input type="hidden" name="action" value="upload">
<input name="title" placeholder="Document Title">
<select name="related_type"><option value="">Related Module</option><option>Client</option><option>Lead</option><option>Deal</option><option>Commission</option><option>Task</option><option>Portal</option></select>
<input name="related_id" placeholder="Related Record ID">
<select name="visibility_scope"><?php foreach (['company'=>'Company', 'team'=>'Team', 'owner'=>'Owner Only', 'client'=>'Client Portal'] as $value=>$label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
<input type="file" name="document_file" required>
<button type="submit">Upload Document</button>
</form>
</div>
<?php endif; ?>
<div class="card">
<h3>Stored Documents</h3>
<table>
<thead><tr><th>Title</th><th>Related</th><th>Access</th><th>File</th><th>Uploaded</th><th></th></tr></thead>
<tbody>
<?php foreach ($documents as $row): ?>
<tr>
<td><?= e($row['title']) ?></td>
<td><?= e(($row['related_type'] ?: 'General') . ($row['related_id'] ? ' #' . $row['related_id'] : '')) ?></td>
<td><span class="tag"><?= e($row['visibility_scope']) ?></span></td>
<td><a href="index.php?page=document_download&id=<?= e((string)$row['id']) ?>"><?= e($row['original_name']) ?></a><div class="muted"><?= e($row['mime_type']) ?> · <?= e((string)$row['file_size']) ?> bytes</div></td>
<td><?= e($row['created_at']) ?></td>
<td class="table-actions"><?php if ($canDeleteDocuments): ?><form method="post" onsubmit="return confirm('Delete this document?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e((string)$row['id']) ?>"><button class="btn-danger" type="submit">Delete</button></form><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
