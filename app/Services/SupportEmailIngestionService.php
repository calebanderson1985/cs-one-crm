<?php
namespace App\Services;

use App\Models\Notification;
use App\Models\SupportEmailIngestion;
use App\Models\SupportTicket;
use App\Models\SupportTicketComment;
use App\Models\SupportTicketAttachment;
use App\Services\AttachmentScanService;
use PDO;

class SupportEmailIngestionService {
    public function __construct(private PDO $db) {}

    public function ingest(array $payload): array {
        $companyId = max(1, (int)($payload['company_id'] ?? current_company_id()));
        $_SERVER['HTTP_X_COMPANY_ID'] = (string)$companyId;

        $fromEmail = strtolower(trim((string)($payload['from_email'] ?? $payload['sender_email'] ?? '')));
        $fromName = trim((string)($payload['from_name'] ?? $payload['sender_name'] ?? ''));
        $subject = trim((string)($payload['subject'] ?? $payload['subject_line'] ?? '(No subject)'));
        $body = trim((string)($payload['body_text'] ?? $payload['text'] ?? $payload['plain'] ?? ''));
        $messageId = trim((string)($payload['message_id'] ?? $payload['source_message_id'] ?? '')) ?: null;
        $inReplyTo = trim((string)($payload['in_reply_to'] ?? '')) ?: null;
        $references = trim((string)($payload['references'] ?? ''));

        $ticketModel = new SupportTicket($this->db);
        $commentModel = new SupportTicketComment($this->db);

        $ticket = null;
        $ticketId = $this->extractTicketId($subject);
        if ($ticketId > 0) {
            $ticket = $ticketModel->getById($ticketId);
        }
        if (!$ticket && ($inReplyTo || $references || $messageId)) {
            $ticket = $ticketModel->findByConversationReference($messageId, $inReplyTo, $references);
        }

        $isNew = false;
        if (!$ticket) {
            $ticketId = $ticketModel->create([
                'title' => $this->normalizeSubject($subject),
                'category_name' => trim((string)($payload['category_name'] ?? 'Email Intake')),
                'priority_name' => trim((string)($payload['priority_name'] ?? 'Normal')),
                'status_name' => 'Open',
                'detail_text' => $body,
                'requester_name' => $fromName,
                'requester_email' => $fromEmail,
                'source_channel' => 'Email',
                'thread_ref' => $messageId ?: sha1($subject . '|' . $fromEmail),
                'touch_inbound' => true,
            ]);
            $ticket = $ticketModel->getById($ticketId);
            $isNew = true;
        } else {
            $ticketId = (int)$ticket['id'];
            $ticketModel->markInbound($ticketId, $fromName, $fromEmail, $messageId ?: null);
        }

        $parentId = 0;
        if ($inReplyTo) {
            $parentId = $commentModel->findIdBySourceMessageId($ticketId, $inReplyTo);
        }
        if ($parentId <= 0 && $references) {
            foreach (preg_split('/\s+/', $references) as $ref) {
                $parentId = $commentModel->findIdBySourceMessageId($ticketId, trim($ref));
                if ($parentId > 0) {
                    break;
                }
            }
        }

        $commentId = $commentModel->createForTicket($ticketId, [
            'visibility_scope' => 'client',
            'comment_text' => $body !== '' ? $body : '(No body received)',
            'parent_comment_id' => $parentId,
            'message_direction' => 'inbound',
            'message_source' => 'email',
            'source_message_id' => $messageId,
            'sender_name' => $fromName,
            'sender_email' => $fromEmail,
            'thread_ref' => $ticket['thread_ref'] ?? ($messageId ?: null),
            'system_user_id' => null,
        ]);

        foreach ((array)($payload['attachments'] ?? []) as $attachment) {
            $attachmentId = (new SupportTicketAttachment($this->db))->createFromPayload($ticketId, $commentId, (array)$attachment, 'email_ingest');
            if ($attachmentId) {
                (new AttachmentScanService($this->db))->queueSupportAttachment($attachmentId);
            }
        }

        (new CommunicationService($this->db))->logInbound([
            'related_type' => 'SupportTicket',
            'related_id' => $ticketId,
            'channel' => 'Email',
            'recipient' => $fromEmail,
            'subject_line' => $subject,
            'body_text' => $body,
            'provider_name' => 'Email Ingest',
        ]);

        (new SupportEmailIngestion($this->db))->create([
            'company_id' => $companyId,
            'ticket_id' => $ticketId,
            'from_email' => $fromEmail,
            'subject_line' => $subject,
            'source_message_id' => $messageId,
            'status_name' => 'Processed',
            'notes_text' => $isNew ? 'Created new ticket from email.' : 'Added inbound email comment to existing ticket.',
        ]);

        $notifyUser = !empty($ticket['owner_user_id']) ? (int)$ticket['owner_user_id'] : null;
        (new Notification($this->db))->create([
            'user_id' => $notifyUser,
            'title' => $isNew ? 'New email ticket' : 'Email reply received',
            'message_text' => 'Ticket #' . $ticketId . ' received email from ' . ($fromEmail ?: 'unknown sender') . '.',
            'level_name' => 'info',
            'link_url' => 'index.php?page=support',
            'company_id' => $companyId,
        ]);

        audit_log($this->db, 'support', 'email_ingest', $ticketId, ($isNew ? 'Created' : 'Updated') . ' ticket from inbound email');

        return [
            'status' => 'ok',
            'ticket_id' => $ticketId,
            'comment_id' => $commentId,
            'created_ticket' => $isNew,
        ];
    }

    private function extractTicketId(string $subject): int {
        if (preg_match('/\[\s*Ticket\s*#(\d+)\s*\]/i', $subject, $m)) {
            return (int)$m[1];
        }
        return 0;
    }

    private function normalizeSubject(string $subject): string {
        $subject = preg_replace('/\[\s*Ticket\s*#\d+\s*\]/i', '', $subject) ?? $subject;
        $subject = preg_replace('/^\s*(re|fw|fwd)\s*:\s*/i', '', $subject) ?? $subject;
        $subject = trim($subject);
        return $subject !== '' ? $subject : 'Email Ticket';
    }
}
