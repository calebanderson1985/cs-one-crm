<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\KnowledgeBaseArticle;
use App\Models\Notification;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketComment;
use App\Models\User;
use App\Services\AttachmentScanService;

class SupportPortalController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requireLogin();
        if (current_user_role() !== 'client') {
            http_response_code(403);
            exit('Forbidden');
        }
        $ticketModel = new SupportTicket($this->db);
        $commentModel = new SupportTicketComment($this->db);
        $attachmentModel = new SupportTicketAttachment($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                $id = $ticketModel->create([
                    'title' => $_POST['title'] ?? 'Support Request',
                    'category_name' => $_POST['category_name'] ?? 'Client Request',
                    'priority_name' => $_POST['priority_name'] ?? 'Normal',
                    'status_name' => 'Open',
                    'detail_text' => $_POST['detail_text'] ?? '',
                    'requester_name' => current_user_name(),
                    'requester_email' => current_user_email(),
                    'source_channel' => 'Portal',
                    'touch_inbound' => true,
                ]);
                $commentId = $commentModel->createForTicket($id, [
                    'visibility_scope' => 'client',
                    'comment_text' => $_POST['detail_text'] ?? '',
                    'message_direction' => 'inbound',
                    'message_source' => 'portal',
                    'sender_name' => current_user_name(),
                    'sender_email' => current_user_email(),
                    'system_user_id' => current_user_id(),
                ]);
                $attachmentId = $attachmentModel->createFromUpload($id, $commentId, $_FILES['attachment_file'] ?? null, 'portal');
                if ($attachmentId) {
                    (new AttachmentScanService($this->db))->queueSupportAttachment($attachmentId);
                }
                $this->notifySupportTeam('New customer portal ticket', 'Ticket #' . $id . ' was created from the client portal.');
                flash('success', 'Your ticket has been created.');
            } elseif ($action === 'reply') {
                $ticketId = (int)($_POST['ticket_id'] ?? 0);
                $ticket = $ticketModel->getById($ticketId);
                if ($ticket && strcasecmp((string)($ticket['requester_email'] ?? ''), current_user_email()) === 0) {
                    $commentId = $commentModel->createForTicket($ticketId, [
                        'visibility_scope' => 'client',
                        'comment_text' => $_POST['comment_text'] ?? '',
                        'parent_comment_id' => (int)($_POST['parent_comment_id'] ?? 0),
                        'message_direction' => 'inbound',
                        'message_source' => 'portal',
                        'sender_name' => current_user_name(),
                        'sender_email' => current_user_email(),
                        'thread_ref' => $ticket['thread_ref'] ?? null,
                        'system_user_id' => current_user_id(),
                    ]);
                    $ticketModel->markInbound($ticketId, current_user_name(), current_user_email(), null);
                    $attachmentId = $attachmentModel->createFromUpload($ticketId, $commentId, $_FILES['attachment_file'] ?? null, 'portal');
                    if ($attachmentId) {
                        (new AttachmentScanService($this->db))->queueSupportAttachment($attachmentId);
                    }
                    $this->notifySupportTeam('Customer replied', 'Ticket #' . $ticketId . ' has a new portal reply.');
                    flash('success', 'Reply posted.');
                } else {
                    flash('error', 'Ticket not found.');
                }
            }
            redirect('index.php?page=support_portal');
        }

        $tickets = array_values(array_filter($ticketModel->list(['q' => current_user_email()]), fn($row) => strcasecmp((string)($row['requester_email'] ?? ''), current_user_email()) === 0));
        $commentsByTicket = $commentModel->groupedForTickets(array_column($tickets, 'id'));
        $attachmentsByTicket = [];
        foreach ($tickets as $ticket) {
            $attachmentsByTicket[(int)$ticket['id']] = $attachmentModel->listForTicket((int)$ticket['id']);
        }
        $articles = (new KnowledgeBaseArticle($this->db))->listClientVisible();
        View::render('portal/support_portal', compact('tickets', 'commentsByTicket', 'attachmentsByTicket', 'articles'));
    }

    private function notifySupportTeam(string $title, string $message): void {
        $users = (new User($this->db))->list();
        foreach ($users as $user) {
            if (in_array($user['role'], ['admin','manager','agent'], true)) {
                (new Notification($this->db))->create([
                    'user_id' => (int)$user['id'],
                    'title' => $title,
                    'message_text' => $message,
                    'level_name' => 'info',
                    'link_url' => 'index.php?page=support',
                ]);
            }
        }
    }
}
