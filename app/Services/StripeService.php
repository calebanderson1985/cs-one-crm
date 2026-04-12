<?php
namespace App\Services;

use PDO;

class StripeService {
    public function __construct(private PDO $db) {}

    public function buildCheckoutPayload(array $subscription): array {
        $companyId = current_company_id();
        $mode = setting($this->db, 'stripe_mode', 'test');
        $publicKey = setting($this->db, 'stripe_public_key', '');
        $successUrl = setting($this->db, 'billing_checkout_success_url', '');
        $cancelUrl = setting($this->db, 'billing_checkout_cancel_url', '');
        $checkoutToken = $this->signCheckoutToken([
            'company_id' => $companyId,
            'subscription_id' => $subscription['id'] ?? null,
            'plan_code' => $subscription['plan_code'] ?? ($subscription['plan_name'] ?? 'default'),
            'ts' => time(),
        ]);
        return [
            'mode' => $mode,
            'public_key' => $publicKey,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'company_id' => $companyId,
            'subscription' => $subscription,
            'checkout_token' => $checkoutToken,
            'checkout_url' => 'public/webhook.php?action=checkout_preview&token=' . rawurlencode($checkoutToken),
        ];
    }

    public function signCheckoutToken(array $payload): string {
        $secret = (string)setting($this->db, 'stripe_secret_key', 'change-me');
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $sig = hash_hmac('sha256', $json ?: '{}', $secret);
        return rtrim(strtr(base64_encode(($json ?: '{}') . '.' . $sig), '+/', '-_'), '=');
    }

    public function validateCheckoutToken(string $token): ?array {
        $decoded = base64_decode(strtr($token, '-_', '+/'), true);
        if (!$decoded || !str_contains($decoded, '.')) {
            return null;
        }
        [$json, $sig] = explode('.', $decoded, 2);
        $expected = hash_hmac('sha256', $json, (string)setting($this->db, 'stripe_secret_key', 'change-me'));
        if (!hash_equals($expected, $sig)) {
            return null;
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    public function verifyWebhook(string $payload, string $signatureHeader): bool {
        $secret = (string)setting($this->db, 'stripe_webhook_secret', '');
        if ($secret === '' || $signatureHeader === '') {
            return false;
        }
        $parsed = $this->parseSignatureHeader($signatureHeader);
        $timestamp = (int)($parsed['t'] ?? 0);
        $signature = (string)($parsed['v1'] ?? '');
        if ($timestamp <= 0 || $signature === '') {
            return false;
        }
        $tolerance = max(60, (int)setting($this->db, 'stripe_webhook_tolerance_seconds', '300'));
        if (abs(time() - $timestamp) > $tolerance) {
            return false;
        }
        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);
        return hash_equals($expected, $signature);
    }

    public function processWebhookEvent(array $event): array {
        $type = (string)($event['type'] ?? 'unknown');
        $object = $event['data']['object'] ?? [];
        $metadata = is_array($object['metadata'] ?? null) ? $object['metadata'] : [];
        $companyId = (int)($metadata['company_id'] ?? current_company_id());

        if (in_array($type, ['checkout.session.completed', 'invoice.paid', 'customer.subscription.updated'], true)) {
            $status = (string)($object['status'] ?? ($type === 'invoice.paid' ? 'paid' : 'active'));
            $externalId = (string)($object['subscription'] ?? ($object['id'] ?? ''));
            $stmt = $this->db->prepare('UPDATE subscriptions SET status = ?, external_reference = ?, updated_at = NOW() WHERE company_id = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([$status, $externalId ?: null, $companyId]);
        }

        return ['type' => $type, 'company_id' => $companyId];
    }

    private function parseSignatureHeader(string $header): array {
        $parts = [];
        foreach (explode(',', $header) as $segment) {
            if (!str_contains($segment, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $segment, 2));
            $parts[$key] = $value;
        }
        return $parts;
    }
}
