<?php
require dirname(__DIR__) . '/bootstrap.php';

if (!isset($pdo)) {
    json_response(['status' => 'error', 'message' => 'Application is not installed.'], 500);
}

$companyId = (int)($_POST['company_id'] ?? $_GET['company_id'] ?? $_SERVER['HTTP_X_COMPANY_ID'] ?? 1);
$_SERVER['HTTP_X_COMPANY_ID'] = (string)$companyId;
$providedToken = trim((string)($_SERVER['HTTP_X_INGEST_TOKEN'] ?? $_POST['token'] ?? $_GET['token'] ?? ''));
$authHeader = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
if ($providedToken === '' && str_starts_with(strtolower($authHeader), 'bearer ')) {
    $providedToken = trim(substr($authHeader, 7));
}
$expectedToken = setting($pdo, 'support_ingest_token', '');
if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    json_response(['status' => 'error', 'message' => 'Unauthorized ingest token.'], 401);
}

$payload = $_POST ?: parse_json_input();
$payload['company_id'] = $companyId;
try {
    $result = (new App\Services\SupportEmailIngestionService($pdo))->ingest($payload);
    json_response($result, 200);
} catch (Throwable $e) {
    audit_log($pdo, 'support', 'email_ingest_error', null, $e->getMessage());
    json_response(['status' => 'error', 'message' => $e->getMessage()], 500);
}
