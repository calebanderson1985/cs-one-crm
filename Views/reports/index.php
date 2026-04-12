<div class="page-header"><div><h2>Reports</h2><p>Filtered reporting for pipeline, commissions, communications, tasks, and automation workload.</p></div><div class="actions"><a href="index.php?page=reports&export=deals">Export Deals</a><a href="index.php?page=reports&export=leads">Export Leads</a><a href="index.php?page=reports&export=tasks">Export Tasks</a><a href="index.php?page=reports&export=communications">Export Communications</a><a href="index.php?page=reports&export=commissions">Export Commissions</a></div></div>
<div class="card">
<form method="get" class="inline-form">
<input type="hidden" name="page" value="reports">
<input type="date" name="date_from" value="<?= e($filters['date_from']) ?>">
<input type="date" name="date_to" value="<?= e($filters['date_to']) ?>">
<select name="user_id"><option value="">All Users</option><?php foreach ($users as $user): ?><option value="<?= e((string)$user['id']) ?>" <?= ((string)$filters['user_id'] === (string)$user['id']) ? 'selected' : '' ?>><?= e($user['full_name']) ?></option><?php endforeach; ?></select>
<button type="submit">Apply Filters</button>
<a class="btn-muted" href="index.php?page=reports">Reset</a>
</form>
</div>
<div class="stats-grid compact">
    <div class="card stat"><span>Total Deal Value</span><strong><?= money($summary['deal_value']) ?></strong></div>
    <div class="card stat"><span>Commission Due</span><strong><?= money($summary['commission_due']) ?></strong></div>
    <div class="card stat"><span>Lead Count</span><strong><?= (int)$summary['lead_count'] ?></strong></div>
    <div class="card stat"><span>Task Count</span><strong><?= (int)$summary['task_count'] ?></strong></div>
    <div class="card stat"><span>Messages</span><strong><?= (int)$summary['message_count'] ?></strong></div>
    <div class="card stat"><span>Workflow Queue</span><strong><?= (int)$summary['workflow_queue'] ?></strong></div>
</div>
<div class="grid-three">
<div class="card"><h3>Pipeline Value by Stage</h3><table><thead><tr><th>Stage</th><th>Value</th></tr></thead><tbody><?php foreach ($pipeline as $stage => $value): ?><tr><td><?= e($stage) ?></td><td><?= money($value) ?></td></tr><?php endforeach; ?></tbody></table></div>
<div class="card"><h3>Commissions by Agent</h3><table><thead><tr><th>Agent</th><th>Total</th></tr></thead><tbody><?php foreach ($commissionByAgent as $agent => $value): ?><tr><td><?= e($agent) ?></td><td><?= money($value) ?></td></tr><?php endforeach; ?></tbody></table></div>
<div class="card"><h3>Activity by Channel</h3><table><thead><tr><th>Channel</th><th>Messages</th></tr></thead><tbody><?php foreach ($activityByChannel as $channel => $count): ?><tr><td><?= e($channel) ?></td><td><?= e((string)$count) ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
<div class="card">
<table>
<thead><tr><th>Report</th><th>Category</th><th>Status</th></tr></thead>
<tbody><?php foreach ($reports as $report): ?><tr><td><?= e($report['report_name']) ?></td><td><?= e($report['category_name']) ?></td><td><?= e($report['status']) ?></td></tr><?php endforeach; ?></tbody>
</table>
</div>
