<?php
namespace App\Services;

use App\Models\MailboxPollConfig;
use PDO;

class MailboxPollService {
    public function __construct(private PDO $db) {}

    public function pollAll(): array {
        $configs = (new MailboxPollConfig($this->db))->getActive();
        $results = [];
        foreach ($configs as $config) {
            $results[] = $this->pollConfig($config);
        }
        return $results;
    }

    public function pollConfig(array $config): array {
        if (!function_exists('imap_open')) {
            return ['config_name' => $config['config_name'], 'status' => 'error', 'message' => 'PHP IMAP extension is not installed.'];
        }
        $mailbox = '{' . $config['host_name'] . ':' . (int)$config['port_number'] . '/imap/' . strtolower((string)$config['encryption_type']) . '}' . ($config['inbox_name'] ?: 'INBOX');
        $stream = @imap_open($mailbox, $config['username_text'], $config['password_text']);
        if (!$stream) {
            return ['config_name' => $config['config_name'], 'status' => 'error', 'message' => (string)imap_last_error()];
        }
        $criteria = ($config['poll_mode'] ?? 'unseen') === 'all' ? 'ALL' : 'UNSEEN';
        $emails = imap_search($stream, $criteria) ?: [];
        $processed = 0;
        foreach ($emails as $emailNumber) {
            $overview = imap_fetch_overview($stream, (string)$emailNumber, 0)[0] ?? null;
            if (!$overview) { continue; }
            $from = $overview->from ?? '';
            preg_match('/<([^>]+)>/', $from, $m);
            $fromEmail = strtolower(trim($m[1] ?? $from));
            $allowedDomain = trim((string)($config['sender_domain_filter'] ?? ''));
            if ($allowedDomain !== '' && !str_ends_with($fromEmail, '@' . ltrim($allowedDomain, '@'))) {
                continue;
            }
            $payload = [
                'company_id' => current_company_id(),
                'from_email' => $fromEmail,
                'from_name' => trim(preg_replace('/<[^>]+>/', '', $from)),
                'subject' => imap_utf8($overview->subject ?? '(No subject)'),
                'body_text' => trim((string)imap_fetchbody($stream, $emailNumber, '1')) ?: trim((string)imap_body($stream, $emailNumber)),
                'message_id' => trim((string)($overview->message_id ?? '')),
                'in_reply_to' => trim((string)($overview->in_reply_to ?? '')),
                'references' => trim((string)($overview->references ?? '')),
            ];
            (new SupportEmailIngestionService($this->db))->ingest($payload);
            @imap_setflag_full($stream, (string)$emailNumber, '\\Seen');
            $processed++;
        }
        imap_close($stream);
        if (!empty($config['id'])) {
            (new MailboxPollConfig($this->db))->touchPolled((int)$config['id']);
        }
        return ['config_name' => $config['config_name'], 'status' => 'ok', 'processed' => $processed];
    }
}
