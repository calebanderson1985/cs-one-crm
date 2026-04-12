<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\WorkerHeartbeat;
use App\Services\CommunicationService;
use App\Services\AiService;

class OpsConsoleController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('ops_console', 'view');
        $heartbeatModel = new WorkerHeartbeat($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            Auth::requirePermission('ops_console', 'edit');
            $action = $_POST['action'] ?? '';
            if ($action === 'test_email') {
                (new CommunicationService($this->db))->queueEmail([
                    'to' => trim((string)($_POST['test_recipient'] ?? current_user_email())),
                    'subject' => 'CS One CRM test email',
                    'body' => 'This is a queued test email from the operations console.',
                ]);
                audit_log($this->db, 'ops_console', 'test_email', null, 'Queued test email');
                flash('success', 'Test email queued.');
            } elseif ($action === 'test_sms') {
                (new CommunicationService($this->db))->queueSms([
                    'to' => trim((string)($_POST['test_phone'] ?? setting($this->db, 'sms_from_number', ''))),
                    'body' => 'CS One CRM test SMS from operations console.',
                ]);
                audit_log($this->db, 'ops_console', 'test_sms', null, 'Queued test SMS');
                flash('success', 'Test SMS queued.');
            } elseif ($action === 'test_ai') {
                $prompt = trim((string)($_POST['ai_prompt'] ?? 'Summarize current CRM operational readiness.'));
                $output = (new AiService($this->db))->generate('ops_console_test', $prompt, ['source' => 'ops_console']);
                $_SESSION['ops_console_ai_result'] = is_array($output) ? json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string)$output;
                audit_log($this->db, 'ops_console', 'test_ai', null, 'AI console test executed');
                flash('success', 'AI test executed.');
            }
            redirect('index.php?page=ops_console');
        }
        $heartbeats = $heartbeatModel->listAll();
        $aiResult = $_SESSION['ops_console_ai_result'] ?? null;
        unset($_SESSION['ops_console_ai_result']);
        View::render('admin/ops_console', compact('heartbeats', 'aiResult'));
    }
}
