<?php
namespace App\Services;

use App\Models\CommunicationTemplate;
use App\Models\Message;
use App\Models\Notification;
use App\Models\OutboundMessage;
use PDO;

class CommunicationService {
    public function __construct(private PDO $db) {}

    public function queue(array $data, bool $attemptImmediate = false): array {
        $templateId = !empty($data['template_id']) ? (int) $data['template_id'] : null;
        $context = $data['context'] ?? [];
        if ($templateId) {
            $rendered = $this->renderTemplate($templateId, $context);
            if (!empty($rendered['subject_line']) && empty($data['subject_line'])) {
                $data['subject_line'] = $rendered['subject_line'];
            }
            if (!empty($rendered['body_text']) && empty($data['body_text'])) {
                $data['body_text'] = $rendered['body_text'];
            }
            $data['channel'] = $data['channel'] ?? ($rendered['channel'] ?? 'Email');
        }

        $channel = strtoupper(($data['channel'] ?? 'Email')) === 'SMS' ? 'SMS' : 'Email';
        $provider = $channel === 'SMS' ? setting($this->db, 'sms_provider', 'SMS Queue') : setting($this->db, 'email_provider', 'Email Queue');
        $recipient = trim((string)($data['recipient'] ?? ''));

        $messageModel = new Message($this->db);
        $communicationId = $messageModel->create([
            'related_type' => $data['related_type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'channel' => $channel,
            'direction' => 'Outbound',
            'recipient' => $recipient,
            'subject_line' => $data['subject_line'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'status' => 'Queued',
            'provider_name' => $provider,
            'template_id' => $templateId,
            'created_by' => $data['created_by'] ?? (current_user_id() ?: null),
        ]);

        $outboundId = (new OutboundMessage($this->db))->create([
            'communication_id' => $communicationId,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject_line' => $data['subject_line'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'provider_name' => $provider,
            'send_status' => 'Queued',
            'scheduled_at' => $data['scheduled_at'] ?? now(),
        ]);

        (new Notification($this->db))->create([
            'user_id' => $data['notify_user_id'] ?? (current_user_id() ?: null),
            'title' => $channel . ' queued',
            'message_text' => 'Outbound ' . $channel . ' prepared for ' . $recipient,
            'level_name' => 'info',
            'link_url' => 'index.php?page=communications',
        ]);

        $result = [
            'communication_id' => $communicationId,
            'outbound_id' => $outboundId,
            'status' => 'Queued',
            'provider' => $provider,
        ];

        return $attemptImmediate ? $this->processOutbound($outboundId) : $result;
    }

    public function queueEmail(array $payload): int {
        $result = $this->queue([
            'channel' => 'Email',
            'recipient' => trim((string)($payload['to'] ?? '')),
            'subject_line' => $payload['subject'] ?? 'CS One CRM Email',
            'body_text' => $payload['body'] ?? '',
        ], false);
        return (int)($result['outbound_id'] ?? 0);
    }

    public function queueSms(array $payload): int {
        $result = $this->queue([
            'channel' => 'SMS',
            'recipient' => trim((string)($payload['to'] ?? '')),
            'body_text' => $payload['body'] ?? '',
        ], false);
        return (int)($result['outbound_id'] ?? 0);
    }

    public function logInbound(array $data): int {
        return (new Message($this->db))->create([
            'related_type' => $data['related_type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'channel' => ucfirst(strtolower($data['channel'] ?? 'Email')),
            'direction' => 'Inbound',
            'recipient' => $data['recipient'] ?? '',
            'subject_line' => $data['subject_line'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'status' => 'Received',
            'provider_name' => $data['provider_name'] ?? null,
            'created_by' => current_user_id() ?: null,
            'sent_at' => now(),
        ]);
    }

    public function renderTemplate(int $templateId, array $context = []): array {
        $template = (new CommunicationTemplate($this->db))->get($templateId);
        if (!$template) {
            return [];
        }
        return [
            'channel' => $template['channel'],
            'subject_line' => render_tokens((string)($template['subject_template'] ?? ''), $context),
            'body_text' => render_tokens((string)($template['body_template'] ?? ''), $context),
        ];
    }

    public function processQueue(int $limit = 25): array {
        $results = [];
        foreach ((new OutboundMessage($this->db))->pending($limit) as $message) {
            $results[] = $this->processOutbound((int)$message['id']);
        }
        return $results;
    }

    public function processOutbound(int $outboundId): array {
        $queueModel = new OutboundMessage($this->db);
        $messageModel = new Message($this->db);
        $queued = $queueModel->get($outboundId);
        if (!$queued) {
            return ['status' => 'Missing', 'error' => 'Queue item not found.'];
        }

        $result = strtoupper((string)$queued['channel']) === 'SMS' ? $this->sendSms($queued) : $this->sendEmail($queued);
        if (!empty($result['success'])) {
            $queueModel->markSent($outboundId, $result['provider'], $result['provider_message_id'] ?? null);
            if (!empty($queued['communication_id'])) {
                $messageModel->setStatus((int)$queued['communication_id'], 'Sent', $result['provider'], now());
            }
            audit_log($this->db, 'communications', 'send', (int)($queued['communication_id'] ?? 0), 'Outbound message sent through ' . $result['provider']);
            return ['status' => 'Sent', 'provider' => $result['provider'], 'communication_id' => $queued['communication_id'] ?? null];
        }

        $queueModel->markFailed($outboundId, $result['provider'] ?? 'Queue', $result['error'] ?? 'Unknown error');
        if (!empty($queued['communication_id'])) {
            $messageModel->setStatus((int)$queued['communication_id'], 'Failed', $result['provider'] ?? 'Queue');
        }
        audit_log($this->db, 'communications', 'fail', (int)($queued['communication_id'] ?? 0), $result['error'] ?? 'Outbound message failed');
        return ['status' => 'Failed', 'provider' => $result['provider'] ?? 'Queue', 'error' => $result['error'] ?? 'Unknown error'];
    }

    private function sendEmail(array $message): array {
        $provider = setting($this->db, 'email_provider', 'PHP Mail');
        $from = setting($this->db, 'email_from_address', 'noreply@example.com');
        $subject = (string)($message['subject_line'] ?? '(no subject)');
        $body = (string)($message['body_text'] ?? '');
        $recipient = (string)$message['recipient'];

        if (function_exists('mail') && stripos($provider, 'php') !== false) {
            $headers = 'From: ' . $from . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
            $ok = @mail($recipient, $subject, $body, $headers);
            return $ok ? ['success' => true, 'provider' => 'PHP mail()'] : ['success' => false, 'provider' => 'PHP mail()', 'error' => 'mail() returned false'];
        }

        if (stripos($provider, 'sendgrid') !== false || stripos($provider, 'mailgun') !== false) {
            return ['success' => false, 'provider' => (string)$provider, 'error' => 'Provider SDK/API wiring is scaffolded; configure live transport in production.'];
        }

        return ['success' => false, 'provider' => (string)$provider, 'error' => 'No email transport available'];
    }

    private function sendSms(array $message): array {
        $provider = setting($this->db, 'sms_provider', 'SMS Queue');
        if (stripos($provider, 'twilio') !== false) {
            return ['success' => false, 'provider' => 'Twilio', 'error' => 'Twilio transport is scaffolded; configure live transport in production.'];
        }
        return ['success' => false, 'provider' => (string)$provider, 'error' => 'SMS provider is not configured'];
    }
}
