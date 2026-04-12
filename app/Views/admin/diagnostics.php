
<div class="page-header"><div><h2>Diagnostics</h2><p>Deployment and runtime checks for production readiness.</p></div></div>
<div class="card"><table><thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead><tbody><?php foreach ($checks as $check): ?><tr><td><?= e($check['label']) ?></td><td><span class="tag"><?= e(strtoupper($check['status'])) ?></span></td><td><?= e($check['detail']) ?></td></tr><?php endforeach; ?></tbody></table></div>
