
<div class="page-header"><div><h2>Webhook Events</h2><p>Billing/provider webhook intake log, verification status, and replay controls.</p></div></div>
<div class="card"><table><thead><tr><th>ID</th><th>Provider</th><th>Event</th><th>Verified</th><th>Status</th><th>Replays</th><th>Created</th><th></th></tr></thead><tbody>
<?php foreach ($events as $event): ?>
<tr>
<td><?= (int)$event['id'] ?></td>
<td><?= e($event['provider_name']) ?></td>
<td><?= e($event['event_type']) ?></td>
<td><span class="tag"><?= !empty($event['is_verified']) ? 'Verified' : 'Unverified' ?></span></td>
<td><?= e($event['processing_status']) ?></td>
<td><?= (int)($event['replay_count'] ?? 0) ?></td>
<td><?= e($event['created_at']) ?></td>
<td><?php if (App\Core\Auth::can('webhooks','edit')): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="replay"><input type="hidden" name="id" value="<?= (int)$event['id'] ?>"><button type="submit" class="btn-muted">Replay</button></form><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
