<?php
$renderThread = function (array $comments, ?int $parentId = null, int $depth = 0) use (&$renderThread) {
    foreach ($comments as $comment) {
        $currentParent = isset($comment['parent_comment_id']) ? (int)$comment['parent_comment_id'] : 0;
        $expected = $parentId ?? 0;
        if ($currentParent !== $expected) { continue; }
        echo '<div class="thread-item" style="margin-left:' . (int)($depth * 18) . 'px">';
        echo '<div class="thread-meta">' . e((string)($comment['sender_name'] ?: $comment['author_name'] ?: 'Support')) . ' · ' . e((string)$comment['created_at']) . ' · ' . e(ucfirst((string)$comment['message_direction'])) . '</div>';
        echo '<div>' . nl2br(e((string)$comment['comment_text'])) . '</div>';
        echo '</div>';
        $renderThread($comments, (int)$comment['id'], $depth + 1);
    }
};
?>
<div class="page-header"><div><h2>Customer Support Portal</h2><p>Open tickets, post replies, upload attachments, and browse help articles.</p></div></div>
<div class="crm-grid crm-grid-sidebar">
<div>
<div class="card form-card"><h3>Create a Ticket</h3><form method="post" enctype="multipart/form-data" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="create"><div class="form-grid form-grid-2"><input type="text" name="title" placeholder="Short summary" required><select name="priority_name"><?php foreach (['Low','Normal','High','Urgent'] as $priority): ?><option value="<?= e($priority) ?>"><?= e($priority) ?></option><?php endforeach; ?></select></div><input type="text" name="category_name" placeholder="Category" value="General Support"><textarea name="detail_text" placeholder="Describe your issue or request" required></textarea><input type="file" name="attachment_file"><button type="submit">Submit Ticket</button></form></div>
<?php foreach ($tickets as $ticket): ?>
<div class="card ticket-card"><div class="split"><div><h3>#<?= e((string)$ticket['id']) ?> · <?= e($ticket['title']) ?></h3><div class="muted"><?= e($ticket['category_name']) ?> · <?= e($ticket['status_name']) ?> · <?= e($ticket['priority_name']) ?></div></div><div class="badge-grid"><span class="badge"><?= e($ticket['status_name']) ?></span></div></div>
<?php $renderThread($commentsByTicket[(int)$ticket['id']] ?? []); ?>
<?php if (!empty($attachmentsByTicket[(int)$ticket['id']])): ?><div class="attachment-list"><?php foreach ($attachmentsByTicket[(int)$ticket['id']] as $file): ?><a class="attachment-pill" href="index.php?page=support_attachment&id=<?= e((string)$file['id']) ?>"><?= e($file['original_name']) ?></a><?php endforeach; ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="stack-form" style="margin-top:12px"><?= csrf_field() ?><input type="hidden" name="action" value="reply"><input type="hidden" name="ticket_id" value="<?= e((string)$ticket['id']) ?>"><textarea name="comment_text" placeholder="Write your reply" required></textarea><input type="file" name="attachment_file"><button type="submit">Post Reply</button></form>
</div>
<?php endforeach; ?>
</div>
<div>
<div class="card"><h3>Help Center</h3><?php foreach ($articles as $article): ?><div class="kb-card"><div class="muted"><?= e($article['category_name']) ?></div><strong><?= e($article['title']) ?></strong><div><?= nl2br(e(substr((string)$article['body_text'],0,240))) ?></div></div><?php endforeach; ?></div>
</div>
</div>
