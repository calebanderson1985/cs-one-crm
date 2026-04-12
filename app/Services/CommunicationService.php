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

        $channel = ucfirst(strtolower($data['channel'] ?? 'Email'));
        $provider = $channel === 'SMS' ? setting($this->db, 'sms_provider', 'SMS Queue') : setting($this->db, 'email_provider', 'Email Queue');
        $messageModel = new Message($this->db);
        $communicationId = $messageModel->create([
            'related_type' => $data['related_type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'channel' => $channel,
            'direction' => 'Outbound',
            'recipient' => $data['recipient'],
            'subject_line' => $data['subject_line'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'status' => 'Queued',
            'provider_name' => $provider,
            'template_id' => $templateId,
            'created_by' => $data['created_by'] ?? current_user_id() ?: null,
        ]);

        $outboundId = (new OutboundMessage($this->db))->create([
            'communication_id' => $communicationId,
            'channel' => $channel,
            'recipient' => $data['recipient'],
            'subject_line' => $data['subject_line'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'provider_name' => $provider,
            'send_status' => 'Queued',
            'scheduled_at' => $data['scheduled_at'] ?? now(),
        ]);

        (new Notification($this->db))->create([
            'user_id' => $data['notify_user_id'] ?? current_user_id() ?: null,
            'title' => $channel . ' queued',
            'message_text' => 'Outbound ' . $channel . ' prepared for ' . $data['recipient'],
            'level_name' => 'info',
            'link_url' => 'index.php?page=communications',
        ]);

        $result = [
            'communication_id' => $communicationId,
            'outbound_id' => $outboundId,
            'status' => 'Queued',
            'provider' => $provider,
        ];

        if ($attemptImmediate) {
            $result = $this->processOutbound($outboundId);
        }

        return $result;
    }

    public function logInbound(array $data): int {
        return (new Message($this->db))->create([
            'related_type' => $data['related_type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'channel' => ucfirst(strtolower($data['channel'] ?? 'Email')),
            'direction' => 'Inbound',
            'recipient' => $data['recipient'],
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
            'subject_line' => render_tokens((string) ($template['subject_template'] ?? ''), $context),
            'body_text' => render_tokens((string) ($template['body_template'] ?? ''), $context),
        ];
    }

    public function processQueue(int $limit = 25): array {
        $results = [];
        foreach ((new OutboundMessage($this->db))->pending($limit) as $message) {
            $results[] = $this->processOutbound((int) $message['id']);
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

        $result = strtoupper($queued['channel']) === 'SMS'
            ? $this->sendSms($queued)
            : $this->sendEmail($queued);

        if (!empty($result['success'])) {
            $queueModel->markSent($outboundId, $result['provider'], $result['provider_message_id'] ?? null);
            if (!empty($queued['communication_id'])) {
                $messageModel->setStatus((int) $queued['communication_id'], 'Sent', $result['provider'], now());
            }
            audit_log($this->db, 'communications', 'send', (int) ($queued['communication_id'] ?? 0), 'Outbound message sent through ' . $result['provider']);
            return ['status' => 'Sent', 'provider' => $result['provider'], 'communication_id' => $queued['communication_id']];
        }

        $queueModel->markFailed($outboundId, $result['provider'] ?? 'Queue', $result['error'] ?? 'Unknown error');
        if (!empty($queued['communication_id'])) {
            $messageModel->setStatus((int) $queued['communication_id'], 'Failed', $result['provider'] ?? 'Queue');
        }
        audit_log($this->db, 'communications', 'fail', (int) ($queued['communication_id'] ?? 0), $result['error'] ?? 'Outbound message failed');
        return ['status' => 'Failed', 'provider' => $result['provider'] ?? 'Queue', 'error' => $result['error'] ?? 'Unknown error'];
    }

    private function sendEmail(array $message): array {
        $provider = setting($this->db, 'email_provider', 'PHP Mail');
        $from = setting($this->db, 'email_from_address', 'noreply@example.com');
        $subject = (string) ($message['subject_line'] ?? '(no subject)');
        $body = (string) ($message['body_text'] ?? '');
        $recipient = (string) $message['recipient'];

        if (stripos($provider, 'sendgrid') !== false && function_exists('curl_init')) {
            $apiKey = setting($this->db, 'email_api_key', '');
            if (!$apiKey) {
                return ['success' => false, 'provider' => 'SendGrid', 'error' => 'Missing SendGrid API key'];
            }
            $payload = json_encode([
                'personalizations' => [['to' => [['email' => $recipient]]]],
                'from' => ['email' => $from],
                'subject' => $subject,
                'content' => [['type' => 'text/plain', 'value' => $body]],
            ]);
            $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT => 20,
            ]);
            curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($httpCode >= 200 && $httpCode < 300) {
                return ['success' => true, 'provider' => 'SendGrid', 'provider_message_id' => null];
            }
            return ['success' => false, 'provider' => 'SendGrid', 'error' => $error ?: 'HTTP ' . $httpCode];
        }

        if (stripos($provider, 'mailgun') !== false && function_exists('curl_init')) {
            $apiKey = setting($this->db, 'email_api_key', '');
            $domain = setting($this->db, 'email_domain', '');
            if (!$apiKey || !$domain) {
                return ['success' => false, 'provider' => 'Mailgun', 'error' => 'Missing Mailgun credentials'];
            }
            $ch = curl_init('https://api.mailgun.net/v3/' . $domain . '/messages');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_USERPWD => 'api:' . $apiKey,
                CURLOPT_POSTFIELDS => [
                    'from' => $from,
                    'to' => $recipient,
                    'subject' => $subject,
                    'text' => $body,
                ],
                CURLOPT_TIMEOUT => 20,
            ]);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($httpCode >= 200 && $httpCode < 300) {
                $decoded = json_decode((string) $response, true);
                return ['success' => true, 'provider' => 'Mailgun', 'provider_message_id' => $decoded['id'] ?? null];
            }
            return ['success' => false, 'provider' => 'Mailgun', 'error' => $error ?: 'HTTP ' . $httpCode];
        }

        if (function_exists('mail')) {
            $headers = 'From: ' . $from . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
            $ok = @mail($recipient, $subject, $body, $headers);
            if ($ok) {
                return ['success' => true, 'provider' => 'PHP mail()'];
            }
            return ['success' => false, 'provider' => 'PHP mail()', 'error' => 'mail() returned false'];
        }

        return ['success' => false, 'provider' => (string) $provider, 'error' => 'No email transport available'];
    }

    private function sendSms(array $message): array {
        $provider = setting($this->db, 'sms_provider', 'SMS Queue');
        $recipient = (string) $message['recipient'];
        $body = (string) ($message['body_text'] ?? '');

        if (stripos($provider, 'twilio') !== false && function_exists('curl_init')) {
            $sid = setting($this->db, 'sms_account_sid', '');
            $token = setting($this->db, 'sms_auth_token', '');
            $from = setting($this->db, 'sms_from_number', '');
            if (!$sid || !$token || !$from) {
                return ['success' => false, 'provider' => 'Twilio', 'error' => 'Missing Twilio credentials'];
            }
            $ch = curl_init('https://api.twilio.com/2010-04-01/Accounts/' . $sid . '/Messages.json');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_USERPWD => $sid . ':' . $token,
                CURLOPT_POSTFIELDS => ['To' => $recipient, 'From' => $from, 'Body' => $body],
                CURLOPT_TIMEOUT => 20,
            ]);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($httpCode >= 200 && $httpCode < 300) {
                $decoded = json_decode((string) $response, true);
                return ['success' => true, 'provider' => 'Twilio', 'provider_message_id' => $decoded['sid'] ?? null];
            }
            return ['success' => false, 'provider' => 'Twilio', 'error' => $error ?: 'HTTP ' . $httpCode];
        }

        return ['success' => false, 'provider' => (string) $provider, 'error' => 'SMS provider is not configured'];
    }
}
