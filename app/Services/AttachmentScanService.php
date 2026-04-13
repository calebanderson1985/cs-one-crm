<?php
namespace App\Services;

use App\Models\AttachmentScanLog;
use App\Models\SupportTicketAttachment;
use PDO;

class AttachmentScanService {
    public function __construct(private PDO $db) {}

    public function queueSupportAttachment(int $attachmentId): void {
        (new AttachmentScanLog($this->db))->queue('support', $attachmentId, 'internal-placeholder');
    }

    public function scanSupportAttachment(int $attachmentId): void {
        $attachment = (new SupportTicketAttachment($this->db))->get($attachmentId);
        if (!$attachment) {
            return;
        }
        $path = dirname(__DIR__, 2) . '/storage/' . $attachment['storage_path'];
        $status = 'passed';
        $summary = 'No risky pattern detected.';
        if (is_file($path)) {
            $content = @file_get_contents($path, false, null, 0, 4096);
            if ($content !== false && preg_match('/<script|eval\(|base64_decode\(|powershell/i', $content)) {
                $status = 'flagged';
                $summary = 'Attachment matched a basic risky-content heuristic.';
            }
        }
        (new AttachmentScanLog($this->db))->complete('support', $attachmentId, $status, $summary);
    }
}
