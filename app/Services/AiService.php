<?php
namespace App\Services;

use App\Models\AiLog;
use App\Models\Client;
use App\Models\Lead;
use PDO;

class AiService {
    public function __construct(private PDO $db) {}

    public function scoreLead(int $leadId): array {
        $leadModel = new Lead($this->db);
        $lead = $leadModel->get($leadId);
        if (!$lead) {
            return ['score' => 0, 'explanation' => 'Lead not found.'];
        }

        $score = 10;
        $reasons = [];
        if (!empty($lead['email'])) { $score += 15; $reasons[] = 'has an email address'; }
        if (!empty($lead['phone'])) { $score += 10; $reasons[] = 'has a phone number'; }
        if (!empty($lead['company_name'])) { $score += 10; $reasons[] = 'includes a company name'; }
        if (($lead['source_name'] ?? '') === 'Referral') { $score += 20; $reasons[] = 'came from a referral'; }
        if (($lead['source_name'] ?? '') === 'Website') { $score += 8; $reasons[] = 'came from the website'; }
        $stageWeights = ['New' => 5, 'Contacted' => 15, 'Qualified' => 25, 'Proposal' => 35, 'Converted' => 50];
        $score += $stageWeights[$lead['stage']] ?? 0;
        $reasons[] = 'is currently in stage ' . $lead['stage'];
        if (!empty($lead['notes']) && strlen((string) $lead['notes']) > 40) { $score += 10; $reasons[] = 'contains meaningful notes'; }
        $score = max(0, min(100, $score));

        $leadModel->setScore($leadId, $score);
        $explanation = 'Lead score ' . $score . '/100 because it ' . implode(', ', $reasons) . '.';
        (new AiLog($this->db))->create([
            'tool_name' => 'lead_score',
            'input_text' => json_encode(['lead_id' => $leadId, 'lead_name' => $lead['lead_name']]),
            'output_text' => $explanation,
        ]);

        return ['score' => $score, 'explanation' => $explanation, 'lead' => $leadModel->get($leadId)];
    }

    public function summarizeClient(int $clientId): array {
        $client = (new Client($this->db))->get($clientId);
        if (!$client) {
            return ['summary' => 'Client not found.'];
        }
        $companyId = current_company_id();
        $dealStmt = $this->db->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(amount),0) AS value_total FROM deals WHERE company_id = ? AND client_name = ?');
        $dealStmt->execute([$companyId, $client['company_name']]);
        $dealStats = $dealStmt->fetch() ?: ['total' => 0, 'value_total' => 0];
        $taskStmt = $this->db->prepare('SELECT COUNT(*) FROM tasks WHERE company_id = ? AND related_type = ? AND related_name = ?');
        $taskStmt->execute([$companyId, 'Client', $client['company_name']]);
        $taskCount = (int) $taskStmt->fetchColumn();
        $commStmt = $this->db->prepare('SELECT COUNT(*) FROM communications WHERE company_id = ? AND ((related_type = ? AND related_id = ?) OR recipient = ?)');
        $commStmt->execute([$companyId, 'Client', $clientId, $client['email']]);
        $commCount = (int) $commStmt->fetchColumn();

        $summary = $client['company_name'] . ' is currently marked ' . $client['status'] . ' with primary contact ' . $client['contact_name'] . '. ' .
            'There are ' . (int) $dealStats['total'] . ' linked deals worth ' . money($dealStats['value_total']) . ', ' . $taskCount . ' related tasks, and ' . $commCount . ' communication records. ' .
            (!empty($client['notes']) ? 'Notes highlight: ' . trim((string) $client['notes']) : 'No major notes are recorded yet.');

        (new AiLog($this->db))->create([
            'tool_name' => 'client_summary',
            'input_text' => json_encode(['client_id' => $clientId, 'client_name' => $client['company_name']]),
            'output_text' => $summary,
        ]);

        return ['summary' => $summary, 'client' => $client];
    }

    public function draftEmail(string $context, string $tone = 'Professional'): array {
        $context = trim($context);
        $toneLabel = ucfirst(strtolower($tone));
        $subject = match (strtolower($tone)) {
            'friendly' => 'Quick follow-up and next steps',
            'urgent' => 'Immediate attention requested',
            default => 'Follow-up regarding your account',
        };
        $body = "Hello,\n\n" .
            "I wanted to follow up regarding " . ($context ?: 'your account') . ". " .
            match (strtolower($tone)) {
                'friendly' => 'I appreciate your time and wanted to make sure you have what you need from us.',
                'urgent' => 'This item is time-sensitive, so I wanted to reach out right away with a clear update and next step.',
                default => 'Below is a concise update along with the recommended next step for moving forward efficiently.',
            } .
            "\n\nNext step: please reply with the best time to connect or any questions you want prioritized.\n\nBest regards,\n" . current_user_name();

        (new AiLog($this->db))->create([
            'tool_name' => 'email_draft',
            'input_text' => json_encode(['tone' => $toneLabel, 'context' => $context]),
            'output_text' => 'Subject: ' . $subject . "\n\n" . $body,
        ]);

        return ['subject' => $subject, 'body' => $body];
    }
}
