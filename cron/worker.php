<?php
require dirname(__DIR__) . '/bootstrap.php';

if (!isset($pdo)) {
    fwrite(STDERR, "Application is not installed.\n");
    exit(1);
}

$companyIds = [];
if (!empty($argv[1])) {
    $companyIds[] = (int) $argv[1];
} else {
    $companyIds = array_map('intval', $pdo->query('SELECT id FROM companies ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN));
}

if (!$companyIds) {
    $companyIds[] = current_company_id();
}

$summary = [];
foreach ($companyIds as $companyId) {
    if ($companyId <= 0) {
        continue;
    }
    $_SERVER['HTTP_X_COMPANY_ID'] = (string) $companyId;
    $batchSize = (int) setting($pdo, 'worker_batch_size', '25');
    $workflowResults = (new App\Services\WorkflowEngine($pdo))->processQueue($batchSize);
    $messageResults = (new App\Services\CommunicationService($pdo))->processQueue($batchSize);
    $supportResults = (new App\Services\SupportEscalationService($pdo))->process();
    $summary[] = [
        'company_id' => $companyId,
        'workflow_jobs' => $workflowResults,
        'message_jobs' => $messageResults,
        'support_escalations' => $supportResults,
    ];
    (new App\Models\WorkerHeartbeat($pdo))->upsert('cron-worker', [
        'status_text' => 'ok',
        'workflow_jobs' => $workflowResults,
        'message_jobs' => $messageResults,
    ]);
}

echo json_encode([
    'companies' => $summary,
    'processed_at' => date('c'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
