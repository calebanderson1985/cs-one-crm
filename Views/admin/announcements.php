<div class="page-header"><div><h2>Announcements</h2><p>Broadcast company, manager, or agent notices from one place.</p></div></div>
<div class="grid cols-2">
  <div class="card">
    <h3>Create announcement</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <label>Title<input type="text" name="title" required></label>
      <label>Audience
        <select name="audience_scope">
          <option value="company">Company-wide</option>
          <option value="admins">Admins only</option>
          <option value="managers">Managers and above</option>
          <option value="agents">Agents and above</option>
        </select>
      </label>
      <label>Message<textarea name="body_text" rows="6" required></textarea></label>
      <label><input type="checkbox" name="is_active" value="1" checked> Mark active</label>
      <button class="btn" type="submit">Publish announcement</button>
    </form>
  </div>
  <div class="card">
    <h3>Existing announcements</h3>
    <table><thead><tr><th>Title</th><th>Audience</th><th>Status</th><th>Created</th><th></th></tr></thead><tbody>
      <?php foreach ($announcements as $row): ?>
      <tr>
        <td><strong><?= e($row['title']) ?></strong><div class="muted"><?= nl2br(e($row['body_text'])) ?></div></td>
        <td><?= e($row['audience_scope']) ?></td>
        <td><?= !empty($row['is_active']) ? 'Active' : 'Inactive' ?></td>
        <td><?= e($row['created_at']) ?></td>
        <td>
          <form method="post" style="display:inline-block"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= e((string)$row['id']) ?>"><button class="btn btn-secondary" type="submit">Toggle</button></form>
          <form method="post" style="display:inline-block" onsubmit="return confirm('Delete announcement?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e((string)$row['id']) ?>"><button class="btn" type="submit">Delete</button></form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
</div>
