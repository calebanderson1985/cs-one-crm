<?php $editing = !empty($editItem); ?>
<div class="page-header"><div><h2>Onboarding & Go-Live Center</h2><p>Track tenant readiness with a shared checklist for setup, communications, permissions, and launch.</p></div><div class="card subtle"><strong><?= e((string)$completionPercent) ?>%</strong><div class="muted">Checklist completion</div></div></div>
<div class="grid-two">
  <div class="card">
    <h3><?= $editing ? 'Edit Checklist Item' : 'Add Checklist Item' ?></h3>
    <form method="post" class="stack-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
      <?php if ($editing): ?><input type="hidden" name="id" value="<?= e((string)$editItem['id']) ?>"><?php endif; ?>
      <input name="category_name" placeholder="Category" value="<?= e($editItem['category_name'] ?? 'Go Live') ?>" required>
      <input name="item_label" placeholder="Checklist Item" value="<?= e($editItem['item_label'] ?? '') ?>" required>
      <input name="item_key" placeholder="System Key" value="<?= e($editItem['item_key'] ?? '') ?>">
      <select name="owner_role"><?php foreach (['admin','manager','agent'] as $role): ?><option value="<?= e($role) ?>" <?= (($editItem['owner_role'] ?? 'admin')===$role)?'selected':''; ?>><?= e(ucfirst($role)) ?></option><?php endforeach; ?></select>
      <input type="number" name="sort_order" placeholder="Sort Order" value="<?= e((string)($editItem['sort_order'] ?? 100)) ?>">
      <label><input type="checkbox" name="is_complete" value="1" <?= !empty($editItem['is_complete']) ? 'checked' : '' ?>> Mark complete</label>
      <div class="actions"><button type="submit"><?= $editing ? 'Save Item' : 'Create Item' ?></button><?php if ($editing): ?><a class="btn-muted" href="index.php?page=onboarding">Cancel</a><?php endif; ?></div>
    </form>
  </div>
  <div class="card">
    <h3>Deployment Readiness</h3>
    <div class="badge-grid">
      <span class="badge">Install</span>
      <span class="badge">Branding</span>
      <span class="badge">Communications</span>
      <span class="badge">Permissions</span>
      <span class="badge">Users</span>
      <span class="badge">Go Live</span>
    </div>
    <p style="margin-top:16px">Use this center as your internal launch worksheet. Phase 7 packages the operational setup into one place so admins can prepare a tenant for production without hunting across menus.</p>
    <div class="note">Recommended next actions: configure email/SMS providers, review permission matrix, create your first team, run the worker, then start importing leads and clients.</div>
  </div>
</div>
<div class="card">
  <h3>Checklist</h3>
  <table>
    <thead><tr><th>Complete</th><th>Category</th><th>Task</th><th>Owner</th><th>Updated</th><th></th></tr></thead>
    <tbody><?php foreach ($items as $row): ?><tr>
      <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= e((string)$row['id']) ?>"><input type="hidden" name="is_complete" value="<?= empty($row['is_complete']) ? '1' : '0' ?>"><button type="submit" class="<?= empty($row['is_complete']) ? '' : 'btn-muted' ?>"><?= empty($row['is_complete']) ? 'Mark Done' : 'Undo' ?></button></form></td>
      <td><?= e($row['category_name']) ?></td>
      <td><?= e($row['item_label']) ?></td>
      <td><span class="tag"><?= e($row['owner_role']) ?></span></td>
      <td><?= e((string)($row['completed_at'] ?: $row['updated_at'])) ?></td>
      <td class="table-actions"><a href="index.php?page=onboarding&id=<?= e((string)$row['id']) ?>">Edit</a><?php if (App\Core\Auth::can('onboarding','delete')): ?><form method="post" onsubmit="return confirm('Delete this checklist item?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e((string)$row['id']) ?>"><button class="btn-danger" type="submit">Delete</button></form><?php endif; ?></td>
    </tr><?php endforeach; ?></tbody>
  </table>
</div>
