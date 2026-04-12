<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\CommunicationTemplate;
use App\Models\Message;
use App\Models\OutboundMessage;
use App\Services\CommunicationService;

class CommunicationController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('communications', 'view');
        $messageModel = new Message($this->db);
        $templateModel = new CommunicationTemplate($this->db);
        $queueModel = new OutboundMessage($this->db);
        $service = new CommunicationService($this->db);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'send';
            if ($action === 'send') {
                Auth::requirePermission('communications', 'create');
                $attemptImmediate = ($_POST['delivery_mode'] ?? 'queue') === 'send_now';
                $result = $service->queue([
                    'channel' => $_POST['channel'] ?? 'Email',
                    'recipient' => trim($_POST['recipient'] ?? ''),
                    'subject_line' => $_POST['subject_line'] ?? '',
                    'body_text' => $_POST['body_text'] ?? '',
                    'template_id' => $_POST['template_id'] ?? null,
                    'related_type' => $_POST['related_type'] ?? null,
                    'related_id' => $_POST['related_id'] ?? null,
                    'context' => ['record' => ['lead_name' => $_POST['lead_name'] ?? '', 'company_name' => $_POST['company_name'] ?? '', 'contact_name' => $_POST['contact_name'] ?? '']],
                ], $attemptImmediate);
                flash(!empty($result['error']) ? 'error' : 'success', !empty($result['error']) ? ('Message failed: ' . $result['error']) : ('Message ' . strtolower($result['status']) . '.'));
            }
            if ($action === 'log_inbound') {
                Auth::requirePermission('communications', 'create');
                $id = $service->logInbound($_POST);
                audit_log($this->db, 'communications', 'receive', $id, 'Inbound communication logged');
                flash('success', 'Inbound communication logged.');
            }
            if ($action === 'update') {
                Auth::requirePermission('communications', 'edit');
                $id = (int) $_POST['id'];
                $messageModel->update($id, $_POST);
                audit_log($this->db, 'communications', 'update', $id, 'Communication updated');
                flash('success', 'Communication updated.');
            }
            if ($action === 'delete') {
                Auth::requirePermission('communications', 'delete');
                $id = (int) $_POST['id'];
                $messageModel->delete($id);
                audit_log($this->db, 'communications', 'delete', $id, 'Communication deleted');
                flash('success', 'Communication deleted.');
            }
            if ($action === 'create_template') {
                Auth::requirePermission('communications', 'create');
                $templateModel->create($_POST);
                flash('success', 'Template created.');
            }
            if ($action === 'update_template') {
                Auth::requirePermission('communications', 'edit');
                $templateModel->update((int) $_POST['id'], $_POST);
                flash('success', 'Template updated.');
            }
            if ($action === 'delete_template') {
                Auth::requirePermission('communications', 'delete');
                $templateModel->delete((int) $_POST['id']);
                flash('success', 'Template deleted.');
            }
            if ($action === 'process_queue') {
                Auth::requirePermission('communications', 'edit');
                $results = $service->processQueue(15);
                $sentCount = count(array_filter($results, fn ($row) => ($row['status'] ?? '') === 'Sent'));
                flash('success', 'Processed outbound queue. Sent: ' . $sentCount . '.');
            }
            redirect('index.php?page=communications');
        }

        $messages = $messageModel->list();
        $editMessage = request_id() ? $messageModel->get(request_id()) : null;
        $templates = $templateModel->list();
        $editTemplate = !empty($_GET['template_id']) ? $templateModel->get((int) $_GET['template_id']) : null;
        $outboundQueue = $queueModel->list();
        View::render('communications/index', compact('messages', 'editMessage', 'templates', 'editTemplate', 'outboundQueue'));
    }
}
