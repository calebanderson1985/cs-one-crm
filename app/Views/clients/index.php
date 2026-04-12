<?php $editing = !empty($editClient); ?>
<div class="page-header"><div><h2>Clients</h2><p>Customer records, ownership, lifecycle status, and relationship notes.</p></div></div>
<div class="grid-two">
<div class="card">
<h3><?= $editing ? 'Edit Client' : 'Add Client' ?></h3>
<form method="post" class="stack-form">
<?= csrf_field() ?>
<input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
<?php if ($editing): ?><input type="hidden" name="id" value="<?= e((string)$editClient['id']) ?>"><?php endif; ?>
<input name="company_name" placeholder="Company Name" value="<?= e($editClient['company_name'] ?? '') ?>" required>
<input name="contact_name" placeholder="Primary Contact" value="<?= e($editClient['contact_name'] ?? '') ?>" required>
<input name="email" placeholder="Email" value="<?= e($editClient['email'] ?? '') ?>">
<input name="phone" placeholder="Phone" value="<?= e($editClient['phone'] ?? '') ?>">
<select name="status"><?php foreach (['Active','Prospect','Inactive'] as $status): ?><option value="<?= e($status) ?>" <?= (($editClient['status'] ?? 'Active') === $status) ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select>
<select name="assigned_user_id"><option value="">Assigned Owner</option><?php foreach ($assignableUsers as $user): ?><option value="<?= e((string)$user['id']) ?>" <?= ((string)($editClient['assigned_user_id'] ?? '') === (string)$user['id']) ? 'selected' : '' ?>><?= e($user['full_name']) ?> · <?= e($user['role']) ?></option><?php endforeach; ?></select>
<textarea name="notes" placeholder="Notes"><?= e($editClient['notes'] ?? '') ?></textarea>
<div class="actions"><button type="submit"><?= $editing ? 'Save Changes' : 'Create Client' ?></button><?php if ($editing): ?><a class="btn-muted" href="index.php?page=clients">Cancel</a><?php endif; ?></div>
</form>
</div>
<div class="card"><h3>Client Directory</h3><table><thead><tr><th>Company</th><th>Contact</th><th>Status</th><th>Owner</th><th></th></tr></thead><tbody><?php foreach ($clients as $row): ?><tr><td><?= e($row['company_name']) ?><div class="muted"><?= e($row['email']) ?></div></td><td><?= e($row['contact_name']) ?><div class="muted"><?= e($row['phone']) ?></div></td><td><span class="tag"><?= e($row['status']) ?></span></td><td><?= e($row['assigned_agent']) ?></td><td class="table-actions"><a href="index.php?page=clients&id=<?= e((string)$row['id']) ?>">Edit</a><?php if (App\Core\Auth::can('clients','delete')): ?><form method="post" onsubmit="return confirm('Delete this client?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e((string)$row['id']) ?>"><button class="btn-danger" type="submit">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
