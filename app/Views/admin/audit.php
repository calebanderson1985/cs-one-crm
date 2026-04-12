<div class="page-header"><div><h2>Audit Trail</h2><p>Search, filter, and export administrative activity history across records, workflow actions, and system changes.</p></div></div>
<div class="card"><form method="get" class="stack-form">
<input type="hidden" name="page" value="audit">
<div class="grid grid-3">
<input type="text" name="module" placeholder="Module" value="<?= e($filters['module'] ?? '') ?>">
<input type="text" name="action" placeholder="Action" value="<?= e($filters['action'] ?? '') ?>">
<input type="text" name="user_id" placeholder="User ID" value="<?= e($filters['user_id'] ?? '') ?>">
<input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>">
<input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>">
<input type="text" name="q" placeholder="Summary or IP" value="<?= e($filters['q'] ?? '') ?>">
</div>
<div class="toolbar"><button type="submit">Apply Filters</button><a class="button button-secondary" href="index.php?page=audit&export=csv&module=<?= urlencode((string)($filters['module'] ?? '')) ?>&action=<?= urlencode((string)($filters['action'] ?? '')) ?>&user_id=<?= urlencode((string)($filters['user_id'] ?? '')) ?>&date_from=<?= urlencode((string)($filters['date_from'] ?? '')) ?>&date_to=<?= urlencode((string)($filters['date_to'] ?? '')) ?>&q=<?= urlencode((string)($filters['q'] ?? '')) ?>">Export CSV</a></div>
</form></div>
<div class="card">
<table>
<thead><tr><th>When</th><th>Module</th><th>Action</th><th>Record</th><th>User</th><th>Summary</th><th>IP</th></tr></thead>
<tbody>
<?php foreach (array_slice($logs, 0, 300) as $row): ?>
<tr>
<td><?= e($row['created_at']) ?></td>
<td><?= e($row['module_name']) ?></td>
<td><span class="tag"><?= e($row['action_name']) ?></span></td>
<td><?= e((string)($row['record_id'] ?? '')) ?></td>
<td><?= e((string)($row['user_id'] ?? '')) ?></td>
<td><?= e((string)($row['summary_text'] ?? '')) ?></td>
<td><?= e((string)($row['ip_address'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
