<div class="page-header"><h2>Feature Registry</h2><p>Unified map of legacy modules into the all-in-one CRM product structure.</p></div>
<div class="card">
<table>
<thead><tr><th>Category</th><th>Feature</th><th>Source Module</th><th>Usage</th></tr></thead>
<tbody><?php foreach ($features as $feature): ?><tr><td><?= e($feature['category_name']) ?></td><td><?= e($feature['feature_name']) ?></td><td><?= e($feature['source_module']) ?></td><td><?= e($feature['usage_summary']) ?></td></tr><?php endforeach; ?></tbody>
</table>
</div>
