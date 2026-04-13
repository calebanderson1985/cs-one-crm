<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\AttachmentScanLog;
use App\Models\MailboxPollConfig;
use App\Models\Notification;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\User;
use App\Models\SlaPolicy;
use App\Models\SupportTicketComment;
use App\Services\AttachmentScanService;
use App\Services\CommunicationService;
use App\Services\MailboxPollService;

class SupportController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('support', 'view');
        $model = new SupportTicket($this->db);
        $commentModel = new SupportTicketComment($this->db);
        $attachmentModel = new SupportTicketAttachment($this->db);
        $mailboxModel = new MailboxPollConfig($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('support', 'create');
                $id = $model->create($_POST);
                $commentId = $commentModel->createForTicket($id, [
                    'visibility_scope' => 'client',
                    'comment_text' => $_POST['detail_text'] ?? '',
                    'message_direction' => 'internal',
                    'message_source' => 'web',
                ]);
                $attachmentId = $attachmentModel->createFromUpload($id, $commentId, $_FILES['attachment_file'] ?? null, 'admin');
                if ($attachmentId) {
                    (new AttachmentScanService($this->db))->queueSupportAttachment($attachmentId);
                }
                (new Notification($this->db))->create([
                    'title' => 'New support ticket',
                    'message_text' => 'Ticket #' . $id . ' was created.',
                    'level_name' => 'info',
                    'link_url' => 'index.php?page=support',
                ]);
                audit_log($this->db, 'support', 'create', $id, 'Support ticket created');
                flash('success', 'Support ticket created.');
            } elseif ($action === 'status') {
                Auth::requirePermission('support', 'edit');
                $id = (int)($_POST['id'] ?? 0);
                $status = trim((string)($_POST['status_name'] ?? 'Open'));
                $model->updateStatus($id, $status);
                audit_log($this->db, 'support', 'status', $id, 'Support ticket moved to ' . $status);
                flash('success', 'Support ticket updated.');
            } elseif ($action === 'comment') {
                Auth::requirePermission('support', 'edit');
                $id = (int)($_POST['ticket_id'] ?? 0);
                $ticket = $model->getById($id);
                if (!$ticket) {
                    flash('error', 'Ticket not found.');
                    redirect('index.php?page=support');
                }
                $sendEmail = !empty($_POST['send_email_reply']) && !empty($ticket['requester_email']);
                $messageId = $sendEmail ? '<ticket-' . $id . '-comment-' . bin2hex(random_bytes(6)) . '@csone.local>' : null;
                $commentId = $commentModel->createForTicket($id, [
                    'visibility_scope' => $_POST['visibility_scope'] ?? 'internal',
                    'comment_text' => $_POST['comment_text'] ?? '',
                    'parent_comment_id' => (int)($_POST['parent_comment_id'] ?? 0),
                    'message_direction' => $sendEmail ? 'outbound' : 'internal',
                    'message_source' => $sendEmail ? 'email' : 'web',
                    'source_message_id' => $messageId,
                    'sender_name' => current_user_name(),
                    'sender_email' => current_user_email(),
                    'thread_ref' => $ticket['thread_ref'] ?? null,
                ]);
                $attachmentId = $attachmentModel->createFromUpload($id, $commentId, $_FILES['attachment_file'] ?? null, 'admin');
                if ($attachmentId) {
                    (new AttachmentScanService($this->db))->queueSupportAttachment($attachmentId);
                }
                if ($sendEmail) {
                    (new CommunicationService($this->db))->queue([
                        'channel' => 'Email',
                        'recipient' => $ticket['requester_email'],
                        'subject_line' => '[Ticket #' . $id . '] ' . $ticket['title'],
                        'body_text' => trim((string)($_POST['comment_text'] ?? '')),
                        'related_type' => 'SupportTicket',
                        'related_id' => $id,
                    ], false);
                    $model->markOutbound($id);
                }
                $this->notifyRequesterIfVisible($ticket, (string)($_POST['visibility_scope'] ?? 'internal'));
                audit_log($this->db, 'support', 'comment', $id, 'Support ticket comment added');
                flash('success', $sendEmail ? 'Reply queued and comment added.' : 'Comment added.');
            } elseif ($action === 'save_mailbox') {
                Auth::requirePermission('support', 'edit');
                $mailboxModel->createOrUpdate($_POST);
                audit_log($this->db, 'support', 'mailbox_save', null, 'Mailbox polling configuration saved');
                flash('success', 'Mailbox polling settings saved.');
            } elseif ($action === 'poll_mailbox') {
                Auth::requirePermission('support', 'edit');
                $result = (new MailboxPollService($this->db))->pollAll();
                audit_log($this->db, 'support', 'mailbox_poll', null, 'Mailbox poll ran');
                flash('success', 'Mailbox poll finished: ' . json_encode($result));
            } elseif ($action === 'delete') {
                Auth::requirePermission('support', 'delete');
                $id = (int)($_POST['id'] ?? 0);
                $model->delete($id);
                audit_log($this->db, 'support', 'delete', $id, 'Support ticket deleted');
                flash('success', 'Support ticket deleted.');
            }
            redirect('index.php?page=support');
        }

        $filters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'priority' => trim((string)($_GET['priority'] ?? '')),
            'q' => trim((string)($_GET['q'] ?? '')),
        ];
        $tickets = $model->list($filters);
        $users = (new User($this->db))->list();
        $slaPolicies = (new SlaPolicy($this->db))->listActive();
        $commentsByTicket = $commentModel->groupedForTickets(array_column($tickets, 'id'));
        $attachmentsByTicket = [];
        foreach ($tickets as $ticket) {
            $attachmentsByTicket[(int)$ticket['id']] = $attachmentModel->listForTicket((int)$ticket['id']);
        }
        $mailboxes = $mailboxModel->list();
        View::render('admin/support', compact('tickets', 'filters', 'users', 'slaPolicies', 'commentsByTicket', 'attachmentsByTicket', 'mailboxes'));
    }

    private function notifyRequesterIfVisible(array $ticket, string $visibility): void {
        if ($visibility !== 'client' || empty($ticket['requester_email'])) {
            return;
        }
        (new Notification($this->db))->create([
            'title' => 'Customer-facing support update',
            'message_text' => 'A client-visible update was added to ticket #' . (int)$ticket['id'] . '.',
            'level_name' => 'info',
            'link_url' => 'index.php?page=support&id=' . (int)$ticket['id'],
        ]);
    }
}
