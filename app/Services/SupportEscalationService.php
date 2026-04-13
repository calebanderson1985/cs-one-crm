<?php
namespace App\Services;

use App\Models\Notification;
use App\Models\SupportEscalationRule;
use App\Models\SupportTicketComment;
use PDO;

class SupportEscalationService {
    public function __construct(private PDO $db) {}

    public function process(): array {
        $rules = (new SupportEscalationRule($this->db))->activeRules();
        if (!$rules) {
            return ['checked' => 0, 'escalated' => 0];
        }

        $stmt = $this->db->prepare("SELECT * FROM support_tickets WHERE company_id = ? AND resolution_due_at IS NOT NULL AND resolution_due_at < NOW() AND status_name NOT IN ('Resolved','Closed') ORDER BY resolution_due_at ASC, id ASC");
        $stmt->execute([current_company_id()]);
        $tickets = $stmt->fetchAll();
        $checked = count($tickets);
        $escalated = 0;
        $commentModel = new SupportTicketComment($this->db);
        $notificationModel = new Notification($this->db);

        foreach ($tickets as $ticket) {
            foreach ($rules as $rule) {
                if (!$this->matches($ticket, $rule)) {
                    continue;
                }
                $hoursOver = max(0, (time() - strtotime((string)$ticket['resolution_due_at'])) / 3600);
                if ($hoursOver < (int)($rule['hours_after_breach'] ?? 0)) {
                    continue;
                }
                $summary = '[auto-escalation:' . (int)$rule['id'] . ']';
                $alreadyStmt = $this->db->prepare('SELECT id FROM support_ticket_comments WHERE company_id = ? AND ticket_id = ? AND comment_text LIKE ? LIMIT 1');
                $alreadyStmt->execute([current_company_id(), (int)$ticket['id'], $summary . '%']);
                if ($alreadyStmt->fetchColumn()) {
                    continue;
                }
                $newPriority = trim((string)($rule['set_priority_name'] ?? '')) ?: (string)$ticket['priority_name'];
                $newStatus = trim((string)($rule['set_status_name'] ?? 'Escalated')) ?: (string)$ticket['status_name'];
                $newOwner = !empty($rule['escalate_to_user_id']) ? (int)$rule['escalate_to_user_id'] : ($ticket['owner_user_id'] ? (int)$ticket['owner_user_id'] : null);
                $update = $this->db->prepare('UPDATE support_tickets SET priority_name = ?, status_name = ?, owner_user_id = ?, updated_at = NOW() WHERE id = ? AND company_id = ?');
                $update->execute([$newPriority, $newStatus, $newOwner, (int)$ticket['id'], current_company_id()]);
                $commentText = $summary . ' ' . render_tokens((string)($rule['comment_template'] ?? 'Ticket auto-escalated.'), ['ticket' => $ticket, 'rule' => $rule]);
                $commentModel->createForTicket((int)$ticket['id'], ['visibility_scope' => 'internal', 'comment_text' => $commentText]);
                $notificationModel->create([
                    'user_id' => $newOwner,
                    'title' => 'Support ticket escalated',
                    'message_text' => 'Ticket #' . (int)$ticket['id'] . ' was auto-escalated by rule ' . (string)$rule['rule_name'] . '.',
                    'level_name' => 'warning',
                    'link_url' => 'index.php?page=support',
                ]);
                audit_log($this->db, 'support', 'escalate', (int)$ticket['id'], 'Ticket auto-escalated by rule #' . (int)$rule['id']);
                $escalated++;
                break;
            }
        }
        return ['checked' => $checked, 'escalated' => $escalated];
    }

    private function matches(array $ticket, array $rule): bool {
        if (!empty($rule['priority_name']) && strcasecmp((string)$ticket['priority_name'], (string)$rule['priority_name']) !== 0) {
            return false;
        }
        if (!empty($rule['category_name']) && strcasecmp((string)$ticket['category_name'], (string)$rule['category_name']) !== 0) {
            return false;
        }
        return true;
    }
}
