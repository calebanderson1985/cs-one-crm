<?php $editing = !empty($editWorkflow); ?>
<div class="page-header"><div><h2>Workflows</h2><p>Automation rules, conditions, action payloads, queue visibility, and run history.</p></div><div class="actions"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="process_queue"><button type="submit" class="btn-muted">Process Workflow Queue</button></form></div></div>
<div class="grid-two">
<div>
<div class="card">
<h3><?= $editing ? 'Edit Workflow' : 'Add Workflow' ?></h3>
<form method="post" class="stack-form">
<?= csrf_field() ?>
<input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
<?php if ($editing): ?><input type="hidden" name="id" value="<?= e((string)$editWorkflow['id']) ?>"><?php endif; ?>
<input name="workflow_name" placeholder="Workflow Name" value="<?= e($editWorkflow['workflow_name'] ?? '') ?>" required>
<input name="module_name" placeholder="Module Category" value="<?= e($editWorkflow['module_name'] ?? '') ?>" required>
<input name="trigger_key" placeholder="Trigger key (example: lead.created)" value="<?= e($editWorkflow['trigger_key'] ?? '') ?>" required>
<select name="action_key"><?php foreach (['send_email','send_sms','create_task','notify_user','score_lead'] as $action): ?><option value="<?= e($action) ?>" <?= (($editWorkflow['action_key'] ?? '') === $action) ? 'selected' : '' ?>><?= e($action) ?></option><?php endforeach; ?></select>
<input name="condition_field" placeholder="Condition field (optional)" value="<?= e($editWorkflow['condition_field'] ?? '') ?>">
<select name="condition_operator"><?php foreach (['equals','not_equals','contains','greater_than','less_than'] as $op): ?><option value="<?= e($op) ?>" <?= (($editWorkflow['condition_operator'] ?? 'equals') === $op) ? 'selected' : '' ?>><?= e($op) ?></option><?php endforeach; ?></select>
<input name="condition_value" placeholder="Condition value" value="<?= e($editWorkflow['condition_value'] ?? '') ?>">
<select name="run_mode"><?php foreach (['queue','instant'] as $mode): ?><option value="<?= e($mode) ?>" <?= (($editWorkflow['run_mode'] ?? 'queue') === $mode) ? 'selected' : '' ?>><?= e($mode) ?></option><?php endforeach; ?></select>
<select name="status"><?php foreach (['Active','Inactive'] as $state): ?><option value="<?= e($state) ?>" <?= (($editWorkflow['status'] ?? 'Active') === $state) ? 'selected' : '' ?>><?= e($state) ?></option><?php endforeach; ?></select>
<textarea name="description_text" placeholder="Description"><?= e($editWorkflow['description_text'] ?? '') ?></textarea>
<textarea name="action_payload" placeholder='Action payload JSON, for example {"subject":"Welcome {{record.lead_name}}","body":"Thanks for reaching out."}'><?= e($editWorkflow['action_payload'] ?? '') ?></textarea>
<div class="actions"><button type="submit"><?= $editing ? 'Save Workflow' : 'Create Workflow' ?></button><?php if ($editing): ?><a class="btn-muted" href="index.php?page=workflows">Cancel</a><?php endif; ?></div>
</form>
</div>
<div class="card"><h3>Queue Snapshot</h3><table><thead><tr><th>Workflow</th><th>Status</th><th>Created</th><th>Error</th></tr></thead><tbody><?php foreach ($queue as $job): ?><tr><td><?= e($job['workflow_name'] ?? 'Workflow') ?></td><td><span class="tag"><?= e($job['queue_status']) ?></span></td><td><?= e($job['created_at']) ?></td><td><?= e((string)($job['error_text'] ?? '')) ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
<div>
<div class="card"><h3>Workflow Library</h3><table><thead><tr><th>Workflow</th><th>Trigger</th><th>Action</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($workflows as $workflow): ?><tr><td><?= e($workflow['workflow_name']) ?><div class="muted"><?= e($workflow['module_name']) ?></div></td><td><?= e($workflow['trigger_key']) ?></td><td><?= e($workflow['action_key']) ?></td><td><span class="tag"><?= e($workflow['status']) ?></span></td><td class="table-actions"><a href="index.php?page=workflows&id=<?= e((string)$workflow['id']) ?>">Edit</a><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="run"><input type="hidden" name="id" value="<?= e((string)$workflow['id']) ?>"><button type="submit">Run Now</button></form><?php if (App\Core\Auth::can('workflows','delete')): ?><form method="post" onsubmit="return confirm('Delete this workflow?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e((string)$workflow['id']) ?>"><button class="btn-danger" type="submit">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<div class="card"><h3>Run History</h3><table><thead><tr><th>Workflow</th><th>Status</th><th>Details</th><th>When</th></tr></thead><tbody><?php foreach (array_slice($runs,0,20) as $run): ?><tr><td><?= e($run['workflow_name']) ?></td><td><span class="tag"><?= e($run['run_status']) ?></span></td><td><?= e($run['details']) ?></td><td><?= e($run['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
</div>
