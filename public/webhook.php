<?php
require dirname(__DIR__) . '/bootstrap.php';
if (!isset($pdo)) {
    http_response_code(503);
    echo 'Application is not installed.';
    exit;
}

use App\Services\StripeService;

$payload = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = json_decode($payload, true) ?: [];
$eventType = $event['type'] ?? 'unknown';
$companyId = !empty($event['data']['object']['metadata']['company_id']) ? (int)$event['data']['object']['metadata']['company_id'] : 1;
$_SERVER['HTTP_X_COMPANY_ID'] = (string)$companyId;

$stripe = new StripeService($pdo);
$verified = $stripe->verifyWebhook($payload, $signature);
$note = $verified ? 'Verified webhook processed' : 'Webhook received (signature not verified)';

$stmt = $pdo->prepare('INSERT INTO audit_logs (company_id, user_id, module_name, action_name, record_id, summary_text, ip_address, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
$stmt->execute([$companyId, null, 'billing', 'webhook', null, $eventType . ' - ' . $note, $_SERVER['REMOTE_ADDR'] ?? null]);

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['received' => true, 'type' => $eventType, 'verified' => $verified]);
