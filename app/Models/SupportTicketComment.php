<?php
namespace App\Models;

class SupportTicketComment extends BaseModel {
    public function listForTicket(int $ticketId): array {
        $sql = 'SELECT stc.*, u.full_name AS author_name
                FROM support_ticket_comments stc
                LEFT JOIN users u ON u.id = stc.user_id AND u.company_id = stc.company_id
                WHERE stc.company_id = ? AND stc.ticket_id = ?';
        $params = [current_company_id(), $ticketId];
        if (current_user_role() === 'client') {
            $sql .= ' AND stc.visibility_scope = ?';
            $params[] = 'client';
        }
        $sql .= ' ORDER BY stc.created_at ASC, stc.id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function groupedForTickets(array $ticketIds): array {
        $ticketIds = array_values(array_filter(array_map('intval', $ticketIds), fn($v) => $v > 0));
        if (!$ticketIds) {
            return [];
        }
        [$inSql, $inParams] = sql_in_clause($ticketIds);
        $sql = 'SELECT stc.*, u.full_name AS author_name
                FROM support_ticket_comments stc
                LEFT JOIN users u ON u.id = stc.user_id AND u.company_id = stc.company_id
                WHERE stc.company_id = ? AND stc.ticket_id IN ' . $inSql;
        $params = array_merge([current_company_id()], $inParams);
        if (current_user_role() === 'client') {
            $sql .= ' AND stc.visibility_scope = ?';
            $params[] = 'client';
        }
        $sql .= ' ORDER BY stc.created_at ASC, stc.id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int)$row['ticket_id']][] = $row;
        }
        return $grouped;
    }

    public function createForTicket(int $ticketId, array $data): int {
        $stmt = $this->db->prepare('INSERT INTO support_ticket_comments (company_id, ticket_id, user_id, parent_comment_id, visibility_scope, message_direction, message_source, source_message_id, sender_name, sender_email, thread_ref, comment_text, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute([
            current_company_id(),
            $ticketId,
            array_key_exists('system_user_id', $data) ? ($data['system_user_id'] ?: null) : (current_user_id() ?: null),
            !empty($data['parent_comment_id']) ? (int)$data['parent_comment_id'] : null,
            trim((string)($data['visibility_scope'] ?? 'internal')) === 'client' ? 'client' : 'internal',
            trim((string)($data['message_direction'] ?? 'internal')) ?: 'internal',
            trim((string)($data['message_source'] ?? 'web')) ?: 'web',
            trim((string)($data['source_message_id'] ?? '')) ?: null,
            trim((string)($data['sender_name'] ?? current_user_name())) ?: null,
            trim((string)($data['sender_email'] ?? current_user_email())) ?: null,
            trim((string)($data['thread_ref'] ?? '')) ?: null,
            trim((string)($data['comment_text'] ?? '')),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findIdBySourceMessageId(int $ticketId, string $messageId): int {
        $stmt = $this->db->prepare('SELECT id FROM support_ticket_comments WHERE company_id = ? AND ticket_id = ? AND source_message_id = ? LIMIT 1');
        $stmt->execute([current_company_id(), $ticketId, trim($messageId)]);
        return (int)($stmt->fetchColumn() ?: 0);
    }
}
