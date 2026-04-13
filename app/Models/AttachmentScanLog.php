<?php
namespace App\Models;

class AttachmentScanLog extends BaseModel {
    public function queue(string $type, int $attachmentId, string $engine = 'internal-placeholder'): void {
        $stmt = $this->db->prepare('INSERT INTO attachment_scan_logs (company_id, attachment_type, attachment_id, scan_status, engine_name, summary_text, created_at, updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([current_company_id(), $type, $attachmentId, 'queued', $engine, 'Queued for attachment safety scan.']);
    }

    public function complete(string $type, int $attachmentId, string $status, string $summary): void {
        $stmt = $this->db->prepare('UPDATE attachment_scan_logs SET scan_status = ?, summary_text = ?, updated_at = NOW() WHERE company_id = ? AND attachment_type = ? AND attachment_id = ?');
        $stmt->execute([$status, $summary, current_company_id(), $type, $attachmentId]);
    }
}
