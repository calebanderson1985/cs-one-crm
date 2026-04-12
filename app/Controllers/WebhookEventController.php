<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\WebhookEvent;
use App\Services\StripeService;

class WebhookEventController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('webhooks', 'view');
        $model = new WebhookEvent($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            Auth::requirePermission('webhooks', 'edit');
            $id = (int) ($_POST['id'] ?? 0);
            $event = $model->get($id);
            if ($event && ($_POST['action'] ?? '') === 'replay') {
                $payload = json_decode((string)($event['payload_text'] ?? ''), true) ?: [];
                $result = (new StripeService($this->db))->processWebhookEvent($payload);
                $model->incrementReplay($id);
                $model->markProcessed($id, 'replayed', json_encode($result, JSON_UNESCAPED_SLASHES));
                audit_log($this->db, 'webhooks', 'replay', $id, 'Webhook event replayed');
                flash('success', 'Webhook event replayed through billing handler.');
                redirect('index.php?page=webhooks');
            }
        }
        $events = $model->list();
        View::render('admin/webhooks', compact('events'));
    }
}
