<div class="page-header"><h2>Portal Center</h2><p>Role-based workspace for Admin, Manager, Agent, and Client portals.</p></div>
<div class="card">
    <h3><?= e(ucfirst($user['role'])) ?> Portal</h3>
    <ul>
        <?php foreach (($portalCards[$user['role']] ?? []) as $item): ?>
        <li><?= e($item) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
