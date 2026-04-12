<?php
require dirname(__DIR__) . '/bootstrap.php';

$checks = [
    'app' => 'ok',
    'php_version' => PHP_VERSION,
    'time' => date(DATE_ATOM),
    'database' => 'unknown',
    'storage_uploads' => is_dir(dirname(__DIR__) . '/storage/uploads') ? 'ok' : 'missing',
    'storage_logs' => is_dir(dirname(__DIR__) . '/storage/logs') ? 'ok' : 'missing',
];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $pdo->query('SELECT 1');
        $checks['database'] = 'ok';
    } catch (Throwable $e) {
        $checks['database'] = 'error';
        $checks['database_error'] = $e->getMessage();
    }
} else {
    $checks['database'] = 'not_configured';
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => in_array('error', $checks, true) ? 'degraded' : 'ok', 'checks' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
