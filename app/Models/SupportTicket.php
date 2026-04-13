<?php
namespace App\Models;

class SupportTicket extends BaseModel {
    public function list(array $filters = []): array {
        [$where, $params] = $this->buildScope('support_tickets');
        $clauses = [];
        if ($where) { $clauses[] = str_replace('support_tickets', 'st', $where); }
        if (!empty($filters['status'])) { $clauses[] = 'st.status_name = ?'; $params[] = $filters['status']; }
        if (!empty($filters['priority'])) { $clauses[] = 'st.priority_name = ?'; $params[] = $filters['priority']; }
        if (!empty($filters['q'])) {
            $clauses[] = '(st.title LIKE ? OR st.detail_text LIKE ? OR st.requester_email LIKE ? OR st.requester_name LIKE ?)';
            for ($i = 0; $i < 4; $i++) { $params[] = '%' . $filters['q'] . '%'; }
        }
        $sql = 'SELECT st.*, sp.policy_name AS sla_policy_name, u.full_name AS owner_name FROM support_tickets st LEFT JOIN sla_policies sp ON sp.id = st.sla_policy_id AND sp.company_id = st.company_id LEFT JOIN users u ON u.id = st.owner_user_id AND u.company_id = st.company_id' . ($clauses ? ' WHERE ' . implode(' AND ', $clauses) : '') . ' ORDER BY COALESCE(st.last_inbound_at, st.updated_at) DESC, st.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $threadRef = trim((string)($data['thread_ref'] ?? '')) ?: null;
        $sourceChannel = trim((string)($data['source_channel'] ?? 'Web')) ?: 'Web';
        $touchInbound = !empty($data['touch_inbound']);
        $stmt = $this->db->prepare('INSERT INTO support_tickets (company_id, title, category_name, priority_name, status_name, owner_user_id, detail_text, requester_name, requester_email, source_channel, thread_ref, last_inbound_at, last_outbound_at, created_by, sla_policy_id, response_due_at, resolution_due_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            current_company_id(),
            trim((string) ($data['title'] ?? '')),
            trim((string) ($data['category_name'] ?? 'General')),
            trim((string) ($data['priority_name'] ?? 'Normal')),
            trim((string) ($data['status_name'] ?? 'Open')),
            !empty($data['owner_user_id']) ? (int) $data['owner_user_id'] : null,
            trim((string) ($data['detail_text'] ?? '')),
            trim((string) ($data['requester_name'] ?? '')) ?: null,
            trim((string) ($data['requester_email'] ?? '')) ?: null,
            $sourceChannel,
            $threadRef,
            $touchInbound ? date('Y-m-d H:i:s') : null,
            null,
            $data['created_by'] ?? (current_user_id() ?: null),
            !empty($data['sla_policy_id']) ? (int) $data['sla_policy_id'] : null,
            $this->calculateDueAt((int)($data['sla_policy_id'] ?? 0), 'response'),
            $this->calculateDueAt((int)($data['sla_policy_id'] ?? 0), 'resolution'),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void {
        $sql = "UPDATE support_tickets SET status_name = ?, resolved_at = CASE WHEN ? IN ('Resolved','Closed') THEN NOW() ELSE NULL END, updated_at = NOW() WHERE id = ? AND company_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $status, $id, current_company_id()]);
    }

    public function markInbound(int $id, ?string $name = null, ?string $email = null, ?string $threadRef = null): void {
        $stmt = $this->db->prepare('UPDATE support_tickets SET requester_name = COALESCE(NULLIF(?,\'\'), requester_name), requester_email = COALESCE(NULLIF(?,\'\'), requester_email), source_channel = CASE WHEN source_channel = \'Web\' THEN \'Email\' ELSE source_channel END, thread_ref = COALESCE(NULLIF(?,\'\'), thread_ref), last_inbound_at = NOW(), updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([(string)$name, (string)$email, (string)$threadRef, $id, current_company_id()]);
    }

    public function markOutbound(int $id): void {
        $stmt = $this->db->prepare('UPDATE support_tickets SET last_outbound_at = NOW(), updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([$id, current_company_id()]);
    }

    private function calculateDueAt(int $slaPolicyId, string $type): ?string {
        if ($slaPolicyId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT response_minutes, resolution_minutes FROM sla_policies WHERE id = ? AND company_id = ? LIMIT 1');
        $stmt->execute([$slaPolicyId, current_company_id()]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $minutes = $type === 'response' ? (int)($row['response_minutes'] ?? 0) : (int)($row['resolution_minutes'] ?? 0);
        if ($minutes <= 0) {
            return null;
        }
        return date('Y-m-d H:i:s', time() + ($minutes * 60));
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT st.*, sp.policy_name AS sla_policy_name, u.full_name AS owner_name FROM support_tickets st LEFT JOIN sla_policies sp ON sp.id = st.sla_policy_id AND sp.company_id = st.company_id LEFT JOIN users u ON u.id = st.owner_user_id AND u.company_id = st.company_id WHERE st.id = ? AND st.company_id = ? LIMIT 1');
        $stmt->execute([$id, current_company_id()]);
        return $stmt->fetch() ?: null;
    }

    public function findByConversationReference(?string $messageId, ?string $inReplyTo, ?string $references = null): ?array {
        $refs = [];
        foreach ([$messageId, $inReplyTo] as $ref) {
            $ref = trim((string)$ref);
            if ($ref !== '') { $refs[] = $ref; }
        }
        foreach (preg_split('/\s+/', trim((string)$references)) ?: [] as $ref) {
            $ref = trim((string)$ref);
            if ($ref !== '') { $refs[] = $ref; }
        }
        $refs = array_values(array_unique($refs));
        if (!$refs) {
            return null;
        }
        [$inSql, $inParams] = sql_in_clause(array_fill(0,0,0));
        $inSql = '(' . implode(',', array_fill(0, count($refs), '?')) . ')';
        $params = array_merge([current_company_id()], $refs, [current_company_id()], $refs);
        $sql = 'SELECT * FROM support_tickets WHERE company_id = ? AND thread_ref IN ' . $inSql . ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([current_company_id()], $refs));
        $ticket = $stmt->fetch();
        if ($ticket) { return $ticket; }
        $sql = 'SELECT st.* FROM support_tickets st INNER JOIN support_ticket_comments stc ON stc.ticket_id = st.id AND stc.company_id = st.company_id WHERE st.company_id = ? AND stc.source_message_id IN ' . $inSql . ' ORDER BY stc.id DESC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([current_company_id()], $refs));
        return $stmt->fetch() ?: null;
    }

    public function slaSummary(): array {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN resolution_due_at IS NOT NULL AND resolution_due_at < NOW() AND status_name NOT IN ('Resolved','Closed') THEN 1 ELSE 0 END) AS breached, SUM(CASE WHEN resolution_due_at IS NOT NULL AND resolution_due_at >= NOW() AND status_name NOT IN ('Resolved','Closed') THEN 1 ELSE 0 END) AS active FROM support_tickets WHERE company_id = ?");
        $stmt->execute([current_company_id()]);
        return $stmt->fetch() ?: ['total' => 0, 'breached' => 0, 'active' => 0];
    }

    public function delete(int $id): void {
        $this->deleteRecord('support_tickets', $id);
    }
}
