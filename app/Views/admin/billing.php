<?php $editingSubscription = !empty($editSubscription); $editingInvoice = !empty($editInvoice); ?>
<div class="page-header"><div><h2>Subscription & Billing Center</h2><p>Manage commercial plans, seat counts, renewal windows, and billing records for the current tenant.</p></div></div>
<div class="stats-grid compact">
  <div class="card stat"><span>Current Plan</span><strong><?= e($currentSubscription['plan_name'] ?? 'Growth') ?></strong></div>
  <div class="card stat"><span>Status</span><strong><?= e($currentSubscription['subscription_status'] ?? 'Trial') ?></strong></div>
  <div class="card stat"><span>Seats</span><strong><?= e((string)($currentSubscription['seat_count'] ?? 0)) ?></strong></div>
  <div class="card stat"><span>MRR</span><strong><?= money($currentSubscription['monthly_amount'] ?? 0) ?></strong></div>
</div>
<div class="grid-two">
  <div class="card">
    <h3><?= $editingSubscription ? 'Edit Subscription' : 'Add Subscription' ?></h3>
    <form method="post" class="stack-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editingSubscription ? 'update_subscription' : 'create_subscription' ?>">
      <?php if ($editingSubscription): ?><input type="hidden" name="id" value="<?= e((string)$editSubscription['id']) ?>"><?php endif; ?>
      <input name="plan_name" placeholder="Plan Name" value="<?= e($editSubscription['plan_name'] ?? 'Growth') ?>" required>
      <select name="billing_cycle"><?php foreach (['Monthly','Quarterly','Annual'] as $cycle): ?><option value="<?= e($cycle) ?>" <?= (($editSubscription['billing_cycle'] ?? 'Monthly')===$cycle)?'selected':''; ?>><?= e($cycle) ?></option><?php endforeach; ?></select>
      <select name="subscription_status"><?php foreach (['Trial','Active','Past Due','Canceled'] as $status): ?><option value="<?= e($status) ?>" <?= (($editSubscription['subscription_status'] ?? 'Trial')===$status)?'selected':''; ?>><?= e($status) ?></option><?php endforeach; ?></select>
      <input type="number" name="seat_count" placeholder="Seats" value="<?= e((string)($editSubscription['seat_count'] ?? 5)) ?>">
      <input type="number" step="0.01" name="monthly_amount" placeholder="Monthly Amount" value="<?= e((string)($editSubscription['monthly_amount'] ?? '299.00')) ?>">
      <input type="date" name="renewal_date" value="<?= e($editSubscription['renewal_date'] ?? '') ?>">
      <input type="date" name="trial_ends_at" value="<?= e($editSubscription['trial_ends_at'] ?? '') ?>">
      <textarea name="notes" placeholder="Notes"><?= e($editSubscription['notes'] ?? '') ?></textarea>
      <div class="actions"><button type="submit"><?= $editingSubscription ? 'Save Subscription' : 'Create Subscription' ?></button><?php if ($editingSubscription): ?><a class="btn-muted" href="index.php?page=billing">Cancel</a><?php endif; ?></div>
    </form>
  </div>
  <div class="card">
    <h3><?= $editingInvoice ? 'Edit Invoice' : 'Add Invoice' ?></h3>
    <form method="post" class="stack-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editingInvoice ? 'update_invoice' : 'create_invoice' ?>">
      <?php if ($editingInvoice): ?><input type="hidden" name="id" value="<?= e((string)$editInvoice['id']) ?>"><?php endif; ?>
      <input name="invoice_number" placeholder="Invoice Number" value="<?= e($editInvoice['invoice_number'] ?? ('INV-' . date('YmdHis'))) ?>" required>
      <input name="plan_name" placeholder="Plan Name" value="<?= e($editInvoice['plan_name'] ?? ($currentSubscription['plan_name'] ?? 'Growth')) ?>" required>
      <input type="number" step="0.01" name="amount" placeholder="Amount" value="<?= e((string)($editInvoice['amount'] ?? ($currentSubscription['monthly_amount'] ?? '299.00'))) ?>" required>
      <select name="invoice_status"><?php foreach (['Draft','Pending','Paid','Void'] as $status): ?><option value="<?= e($status) ?>" <?= (($editInvoice['invoice_status'] ?? 'Draft')===$status)?'selected':''; ?>><?= e($status) ?></option><?php endforeach; ?></select>
      <input type="date" name="due_date" value="<?= e($editInvoice['due_date'] ?? '') ?>">
      <input type="date" name="paid_at" value="<?= e($editInvoice['paid_at'] ?? '') ?>">
      <textarea name="notes" placeholder="Notes"><?= e($editInvoice['notes'] ?? '') ?></textarea>
      <div class="actions"><button type="submit"><?= $editingInvoice ? 'Save Invoice' : 'Create Invoice' ?></button><?php if ($editingInvoice): ?><a class="btn-muted" href="index.php?page=billing">Cancel</a><?php endif; ?></div>
    </form>
  </div>
</div>
<div class="grid-two">
  <div class="card"><h3>Subscriptions</h3><table><thead><tr><th>Plan</th><th>Status</th><th>Seats</th><th>Renewal</th><th></th></tr></thead><tbody><?php foreach ($subscriptions as $row): ?><tr><td><?= e($row['plan_name']) ?><div class="muted"><?= money($row['monthly_amount']) ?> / <?= e($row['billing_cycle']) ?></div></td><td><span class="tag"><?= e($row['subscription_status']) ?></span></td><td><?= e((string)$row['seat_count']) ?></td><td><?= e((string)$row['renewal_date']) ?></td><td class="table-actions"><a href="index.php?page=billing&subscription_id=<?= e((string)$row['id']) ?>">Edit</a><?php if (App\Core\Auth::can('billing','delete')): ?><form method="post" onsubmit="return confirm('Delete this subscription?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete_subscription"><input type="hidden" name="id" value="<?= e((string)$row['id']) ?>"><button class="btn-danger" type="submit">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="card"><h3>Invoices</h3><table><thead><tr><th>Invoice</th><th>Amount</th><th>Status</th><th>Due</th><th></th></tr></thead><tbody><?php foreach ($invoices as $row): ?><tr><td><?= e($row['invoice_number']) ?><div class="muted"><?= e($row['plan_name']) ?></div></td><td><?= money($row['amount']) ?></td><td><span class="tag <?= ($row['invoice_status']==='Paid'?'good':($row['invoice_status']==='Pending'?'warn':'')) ?>"><?= e($row['invoice_status']) ?></span></td><td><?= e((string)$row['due_date']) ?></td><td class="table-actions"><a href="index.php?page=billing&invoice_id=<?= e((string)$row['id']) ?>">Edit</a><?php if (App\Core\Auth::can('billing','delete')): ?><form method="post" onsubmit="return confirm('Delete this invoice?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete_invoice"><input type="hidden" name="id" value="<?= e((string)$row['id']) ?>"><button class="btn-danger" type="submit">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
