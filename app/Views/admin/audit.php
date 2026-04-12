<div class="page-header"><div><h2>Audit Trail</h2><p>Administrative activity history across records, workflow actions, and system changes.</p></div></div>
<div class="card">
<table>
<thead><tr><th>When</th><th>Module</th><th>Action</th><th>Record</th><th>User</th><th>Summary</th><th>IP</th></tr></thead>
<tbody>
<?php foreach (array_slice($logs, 0, 200) as $row): ?>
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
