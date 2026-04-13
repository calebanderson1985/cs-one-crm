<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;

class SupportAttachmentController {
    public function __construct(private \PDO $db) {}

    public function download(): void {
        Auth::requireLogin();
        $attachment = (new SupportTicketAttachment($this->db))->get((int)($_GET['id'] ?? 0));
        if (!$attachment) {
            http_response_code(404);
            exit('Attachment not found.');
        }
        $ticket = (new SupportTicket($this->db))->getById((int)$attachment['ticket_id']);
        if (!$ticket) {
            http_response_code(404);
            exit('Ticket not found.');
        }
        if (current_user_role() === 'client' && strcasecmp((string)($ticket['requester_email'] ?? ''), current_user_email()) !== 0) {
            http_response_code(403);
            exit('Forbidden');
        }
        $fullPath = dirname(__DIR__, 2) . '/storage/' . $attachment['storage_path'];
        if (!is_file($fullPath)) {
            http_response_code(404);
            exit('Stored file is missing.');
        }
        header('Content-Type: ' . ($attachment['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename((string)$attachment['original_name']) . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }
}
