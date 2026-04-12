<?php
require dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'app' => $GLOBALS['pdo'] instanceof PDO ? setting($GLOBALS['pdo'], 'app_name', 'CS One CRM Phase 9') : 'CS One CRM Phase 9',
    'installed' => isset($GLOBALS['pdo']),
    'time' => date('c'),
]);
