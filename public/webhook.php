<?php
require dirname(__DIR__) . '/bootstrap.php';
if (!isset($pdo)) {
    http_response_code(503);
    echo 'Application is not installed.';
    exit;
}

use App\Services\StripeService;

$stripe = new StripeService($pdo);
$action = $_GET['action'] ?? 'webhook';

if ($action === 'checkout_preview') {
    $token = (string)($_GET['token'] ?? '');
    $payload = $stripe->validateCheckoutToken($token);
    if (!$payload) {
        http_response_code(400);
        echo 'Invalid checkout token';
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Checkout Preview</title><style>body{font-family:Arial,sans-serif;padding:2rem;max-width:760px;margin:0 auto;}pre{background:#f4f4f4;padding:1rem;border-radius:8px;}a.button{display:inline-block;padding:0.7rem 1rem;background:#111827;color:#fff;text-decoration:none;border-radius:8px;}</style></head><body>';
    echo '<h1>Stripe Checkout Preview</h1>';
    echo '<p>This Phase 11 build includes a signed checkout preview scaffold. Replace this endpoint with live Stripe SDK checkout session creation for production billing.</p>';
    echo '<pre>' . htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') . '</pre>';
    echo '<p><a class="button" href="' . htmlspecialchars((string)setting($pdo, 'billing_checkout_success_url', '#'), ENT_QUOTES, 'UTF-8') . '">Simulate Success</a></p>';
    echo '</body></html>';
    exit;
}

$payload = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = json_decode($payload, true) ?: [];
$eventType = $event['type'] ?? 'unknown';
$companyId = !empty($event['data']['object']['metadata']['company_id']) ? (int)$event['data']['object']['metadata']['company_id'] : 1;
$_SERVER['HTTP_X_COMPANY_ID'] = (string)$companyId;

$verified = $stripe->verifyWebhook($payload, $signature);
$strict = setting($pdo, 'stripe_webhook_require_verification', '0') === '1';
if ($strict && !$verified) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['received' => false, 'error' => 'Invalid webhook signature']);
    exit;
}

$result = $stripe->processWebhookEvent($event);
$note = $verified ? 'Verified webhook processed' : 'Webhook received (verification bypassed or failed)';

$stmt = $pdo->prepare('INSERT INTO audit_logs (company_id, user_id, module_name, action_name, record_id, summary_text, ip_address, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
$stmt->execute([$companyId, null, 'billing', 'webhook', null, $eventType . ' - ' . $note, client_ip()]);

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['received' => true, 'type' => $eventType, 'verified' => $verified, 'result' => $result]);
