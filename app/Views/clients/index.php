<?php $editing = !empty($editClient); ?>
<div class="page-header"><div><h2>Clients</h2><p>Organized customer records with lifecycle, policy, contact, and address detail.</p></div></div>
<div class="crm-grid crm-grid-sidebar">
<div>
<div class="card form-card">
<h3><?= $editing ? 'Edit Client Profile' : 'Create Client Profile' ?></h3>
<form method="post" class="stack-form">
<?= csrf_field() ?>
<input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
<?php if ($editing): ?><input type="hidden" name="id" value="<?= e((string)$editClient['id']) ?>"><?php endif; ?>
<div class="form-section-title">Core Profile</div>
<div class="form-grid form-grid-2">
<input name="company_name" placeholder="Company Name" value="<?= e($editClient['company_name'] ?? '') ?>" required>
<input name="contact_name" placeholder="Primary Contact" value="<?= e($editClient['contact_name'] ?? '') ?>" required>
<input name="email" placeholder="Email" value="<?= e($editClient['email'] ?? '') ?>">
<input name="phone" placeholder="Phone" value="<?= e($editClient['phone'] ?? '') ?>">
<select name="status"><?php foreach (['Active','Prospect','Inactive'] as $status): ?><option value="<?= e($status) ?>" <?= (($editClient['status'] ?? 'Active') === $status) ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select>
<select name="lifecycle_stage"><?php foreach (['Onboarding','In Service','Renewal','At Risk','Closed'] as $stage): ?><option value="<?= e($stage) ?>" <?= (($editClient['lifecycle_stage'] ?? '') === $stage) ? 'selected' : '' ?>><?= e($stage) ?></option><?php endforeach; ?></select>
</div>
<div class="form-section-title">Business Detail</div>
<div class="form-grid form-grid-3">
<input name="industry_name" placeholder="Industry" value="<?= e($editClient['industry_name'] ?? '') ?>">
<input name="website_url" placeholder="Website URL" value="<?= e($editClient['website_url'] ?? '') ?>">
<input name="policy_type" placeholder="Policy / Product Type" value="<?= e($editClient['policy_type'] ?? '') ?>">
<input type="date" name="renewal_date" value="<?= e($editClient['renewal_date'] ?? '') ?>">
<input type="number" step="0.01" name="annual_revenue" placeholder="Annual Revenue" value="<?= e($editClient['annual_revenue'] ?? '') ?>">
<input type="number" name="employee_count" placeholder="Employee Count" value="<?= e($editClient['employee_count'] ?? '') ?>">
</div>
<div class="form-section-title">Address & Ownership</div>
<div class="form-grid form-grid-2">
<input name="address_line1" placeholder="Address Line 1" value="<?= e($editClient['address_line1'] ?? '') ?>">
<input name="address_line2" placeholder="Address Line 2" value="<?= e($editClient['address_line2'] ?? '') ?>">
<input name="city_name" placeholder="City" value="<?= e($editClient['city_name'] ?? '') ?>">
<input name="state_name" placeholder="State" value="<?= e($editClient['state_name'] ?? '') ?>">
<input name="postal_code" placeholder="Postal Code" value="<?= e($editClient['postal_code'] ?? '') ?>">
<select name="assigned_user_id"><option value="">Assigned Owner</option><?php foreach ($assignableUsers as $user): ?><option value="<?= e((string)$user['id']) ?>" <?= ((string)($editClient['assigned_user_id'] ?? '') === (string)$user['id']) ? 'selected' : '' ?>><?= e($user['full_name']) ?> · <?= e($user['role']) ?></option><?php endforeach; ?></select>
</div>
<textarea name="notes" placeholder="Relationship notes, service details, upcoming needs, or account context"><?= e($editClient['notes'] ?? '') ?></textarea>
<div class="actions"><button type="submit"><?= $editing ? 'Save Changes' : 'Create Client' ?></button><?php if ($editing): ?><a class="btn-muted" href="index.php?page=clients">Cancel</a><?php endif; ?></div>
</form>
</div>
</div>
<div>
<div class="card"><h3>Client Directory</h3><?php foreach ($clients as $row): ?><div class="record-card"><div class="split"><div><strong><?= e($row['company_name']) ?></strong><div class="muted"><?= e($row['contact_name']) ?> · <?= e($row['email']) ?> · <?= e($row['phone']) ?></div></div><div class="badge-grid"><span class="badge"><?= e($row['status']) ?></span><?php if (!empty($row['lifecycle_stage'])): ?><span class="badge"><?= e($row['lifecycle_stage']) ?></span><?php endif; ?></div></div><div class="record-grid"><div><span class="muted">Industry</span><div><?= e($row['industry_name'] ?? '—') ?></div></div><div><span class="muted">Policy</span><div><?= e($row['policy_type'] ?? '—') ?></div></div><div><span class="muted">Renewal</span><div><?= e($row['renewal_date'] ?? '—') ?></div></div><div><span class="muted">Owner</span><div><?= e($row['assigned_agent'] ?? 'Unassigned') ?></div></div></div><div class="actions" style="margin-top:10px"><a class="btn-muted" href="index.php?page=clients&id=<?= e((string)$row['id']) ?>">Edit</a><?php if (App\Core\Auth::can('clients','delete')): ?><form method="post" onsubmit="return confirm('Delete this client?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e((string)$row['id']) ?>"><button class="btn-danger" type="submit">Delete</button></form><?php endif; ?></div></div><?php endforeach; ?></div>
</div>
</div>
