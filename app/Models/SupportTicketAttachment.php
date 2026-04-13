<?php
namespace App\Models;

class SupportTicketAttachment extends BaseModel {
    public function listForTicket(int $ticketId): array {
        $stmt = $this->db->prepare('SELECT * FROM support_ticket_attachments WHERE company_id = ? AND ticket_id = ? ORDER BY created_at ASC, id ASC');
        $stmt->execute([current_company_id(), $ticketId]);
        return $stmt->fetchAll();
    }

    public function createFromUpload(int $ticketId, ?int $commentId, ?array $file, string $source = 'portal'): ?int {
        if (!$file || empty($file['name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$file['name']);
        $storedName = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safeName;
        $relativeDir = 'support_attachments/company_' . current_company_id() . '/ticket_' . $ticketId;
        $targetDir = dirname(__DIR__, 2) . '/storage/' . $relativeDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $relativePath = $relativeDir . '/' . $storedName;
        $fullPath = dirname(__DIR__, 2) . '/storage/' . $relativePath;
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return null;
        }
        return $this->storeRecord($ticketId, $commentId, [
            'original_name' => (string)$file['name'],
            'stored_name' => $storedName,
            'storage_path' => $relativePath,
            'mime_type' => (string)($file['type'] ?? 'application/octet-stream'),
            'file_size' => (int)($file['size'] ?? 0),
            'source_name' => $source,
        ]);
    }

    public function createFromPayload(int $ticketId, ?int $commentId, array $attachment, string $source = 'email_ingest'): ?int {
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)($attachment['filename'] ?? $attachment['name'] ?? 'attachment.bin'));
        $body = (string)($attachment['content_base64'] ?? '');
        if ($body === '') {
            return null;
        }
        $bytes = base64_decode($body, true);
        if ($bytes === false) {
            return null;
        }
        $storedName = time() . '_' . bin2hex(random_bytes(4)) . '_' . $filename;
        $relativeDir = 'support_attachments/company_' . current_company_id() . '/ticket_' . $ticketId;
        $targetDir = dirname(__DIR__, 2) . '/storage/' . $relativeDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $relativePath = $relativeDir . '/' . $storedName;
        $fullPath = dirname(__DIR__, 2) . '/storage/' . $relativePath;
        file_put_contents($fullPath, $bytes);
        return $this->storeRecord($ticketId, $commentId, [
            'original_name' => $filename,
            'stored_name' => $storedName,
            'storage_path' => $relativePath,
            'mime_type' => (string)($attachment['mime_type'] ?? 'application/octet-stream'),
            'file_size' => strlen($bytes),
            'source_name' => $source,
        ]);
    }

    private function storeRecord(int $ticketId, ?int $commentId, array $data): int {
        $stmt = $this->db->prepare('INSERT INTO support_ticket_attachments (company_id, ticket_id, comment_id, original_name, stored_name, storage_path, mime_type, file_size, uploaded_by, source_name, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute([
            current_company_id(),
            $ticketId,
            $commentId,
            $data['original_name'],
            $data['stored_name'],
            $data['storage_path'],
            $data['mime_type'],
            (int)$data['file_size'],
            current_user_id() ?: null,
            $data['source_name'] ?? 'portal',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function get(int $id): ?array {
        return $this->find('support_ticket_attachments', $id);
    }
}
