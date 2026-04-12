<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\AiLog;
use App\Models\Client;
use App\Models\Lead;
use App\Services\AiService;

class AiController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('ai', 'view');
        $service = new AiService($this->db);
        $result = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            Auth::requirePermission('ai', 'create');
            $action = $_POST['action'] ?? 'score_lead';
            if ($action === 'score_lead') {
                $result = $service->scoreLead((int) ($_POST['lead_id'] ?? 0));
            }
            if ($action === 'summarize_client') {
                $result = $service->summarizeClient((int) ($_POST['client_id'] ?? 0));
            }
            if ($action === 'draft_email') {
                $result = $service->draftEmail((string) ($_POST['context'] ?? ''), (string) ($_POST['tone'] ?? 'Professional'));
            }
        }
        $leads = (new Lead($this->db))->list();
        $clients = (new Client($this->db))->list();
        $logs = (new AiLog($this->db))->list(20);
        View::render('ai/index', compact('result', 'leads', 'clients', 'logs'));
    }
}
