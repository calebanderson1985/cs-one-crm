<div class="page-header"><div><h2>Permissions</h2><p>Role-based access control matrix for viewing, creating, editing, and deleting by module.</p></div></div>
<div class="card">
<form method="post">
<?= csrf_field() ?>
<table class="matrix">
<thead>
<tr><th>Module</th><?php foreach ($roles as $role): ?><th colspan="4"><?= e(ucfirst($role)) ?></th><?php endforeach; ?></tr>
<tr><th></th><?php foreach ($roles as $role): ?><?php foreach ($capabilities as $capability): ?><th><?= e(substr($capability,0,1)) ?></th><?php endforeach; ?><?php endforeach; ?></tr>
</thead>
<tbody>
<?php foreach ($modules as $module): ?>
<tr>
<td><?= e($module) ?></td>
<?php foreach ($roles as $role): ?>
<?php foreach ($capabilities as $capability): ?>
<td><input type="checkbox" name="permissions[<?= e($role) ?>][<?= e($module) ?>][<?= e($capability) ?>]" value="1" <?= !empty($matrix[$role][$module][$capability]) ? 'checked' : '' ?>></td>
<?php endforeach; ?>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<div class="actions" style="margin-top:12px"><button type="submit">Save Permissions</button></div>
</form>
</div>
