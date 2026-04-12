<?php
namespace App\Services;

use PDO;

class StripeService {
    public function __construct(private PDO $db) {}

    public function buildCheckoutPayload(array $subscription): array {
        return [
            'mode' => setting($this->db, 'stripe_mode', 'test'),
            'public_key' => setting($this->db, 'stripe_public_key', ''),
            'success_url' => setting($this->db, 'billing_checkout_success_url', ''),
            'cancel_url' => setting($this->db, 'billing_checkout_cancel_url', ''),
            'company_id' => current_company_id(),
            'subscription' => $subscription,
        ];
    }

    public function verifyWebhook(string $payload, string $signature): bool {
        $secret = setting($this->db, 'stripe_webhook_secret', '');
        if ($secret === '' || $signature === '') {
            return false;
        }
        return hash_equals(hash_hmac('sha256', $payload, $secret), hash('sha256', $signature));
    }
}
