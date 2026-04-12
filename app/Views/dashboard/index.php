<div class="page-header"><div><h2>Dashboard</h2><p>Production-grade SaaS CRM overview for core records, communications, automations, and AI activity.</p></div></div>
<div class="stats-grid">
  <div class="card stat"><span>Clients</span><strong><?= e((string)$stats['clients']) ?></strong></div>
  <div class="card stat"><span>Leads</span><strong><?= e((string)$stats['leads']) ?></strong></div>
  <div class="card stat"><span>Deals</span><strong><?= e((string)$stats['deals']) ?></strong></div>
  <div class="card stat"><span>Open Pipeline</span><strong><?= money($stats['open_deal_value']) ?></strong></div>
  <div class="card stat"><span>Open Tasks</span><strong><?= e((string)$stats['tasks_open']) ?></strong></div>
  <div class="card stat"><span>Commissions Due</span><strong><?= money($stats['commissions_due']) ?></strong></div>
  <div class="card stat"><span>Outbound Queue</span><strong><?= e((string)$stats['outbound_queue']) ?></strong></div>
  <div class="card stat"><span>Workflow Queue</span><strong><?= e((string)$stats['workflow_queue']) ?></strong></div>
  <div class="card stat"><span>AI Activity</span><strong><?= e((string)$stats['ai_activity']) ?></strong></div>
  <div class="card stat"><span>Unread Notifications</span><strong><?= e((string)$stats['notifications_unread']) ?></strong></div>
</div>
<div class="grid-two">
  <div>
    <div class="card"><h3 class="section-title">Deal Pipeline</h3><table><thead><tr><th>Stage</th><th>Deals</th><th>Value</th></tr></thead><tbody><?php foreach ($pipeline as $row): ?><tr><td><?= e($row['stage']) ?></td><td><?= e((string)$row['total']) ?></td><td><?= money($row['value_total']) ?></td></tr><?php endforeach; ?></tbody></table></div>
    <div class="card"><h3 class="section-title">Recent Leads</h3><table><thead><tr><th>Lead</th><th>Company</th><th>Stage</th><th>AI Score</th></tr></thead><tbody><?php foreach ($recentLeads as $row): ?><tr><td><?= e($row['lead_name']) ?></td><td><?= e($row['company_name']) ?></td><td><span class="tag"><?= e($row['stage']) ?></span></td><td><?= e((string)($row['ai_score'] ?? 0)) ?></td></tr><?php endforeach; ?></tbody></table></div>
  </div>
  <div>
    <div class="card"><h3 class="section-title">Workflow Queue</h3><table><thead><tr><th>Workflow</th><th>Status</th><th>Trigger</th></tr></thead><tbody><?php foreach ($workflowQueue as $job): ?><tr><td><?= e($job['workflow_name'] ?? 'Workflow') ?></td><td><span class="tag"><?= e($job['queue_status']) ?></span></td><td><?= e($job['trigger_key']) ?></td></tr><?php endforeach; ?></tbody></table></div>
    <div class="card"><h3 class="section-title">Recent Workflow Runs</h3><table><thead><tr><th>Workflow</th><th>Status</th><th>Details</th></tr></thead><tbody><?php foreach ($workflowRuns as $run): ?><tr><td><?= e($run['workflow_name']) ?></td><td><span class="tag"><?= e($run['run_status']) ?></span></td><td><?= e($run['details']) ?></td></tr><?php endforeach; ?></tbody></table></div>
  </div>
</div>
<div class="grid-two">
  <div class="card"><h3 class="section-title">Outbound Communications</h3><table><thead><tr><th>Channel</th><th>Recipient</th><th>Status</th><th>Provider</th></tr></thead><tbody><?php foreach ($outboundQueue as $row): ?><tr><td><?= e($row['channel']) ?></td><td><?= e($row['recipient']) ?></td><td><span class="tag"><?= e($row['send_status']) ?></span></td><td><?= e($row['provider_name']) ?></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="card"><h3 class="section-title">AI Activity Log</h3><table><thead><tr><th>Tool</th><th>Output</th><th>When</th></tr></thead><tbody><?php foreach ($aiLogs as $row): ?><tr><td><?= e($row['tool_name']) ?></td><td><?= e(substr((string)$row['output_text'],0,120)) ?></td><td><?= e($row['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
