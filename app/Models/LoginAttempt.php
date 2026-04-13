<?php
namespace App\Models;

use PDOException;

class LoginAttempt extends BaseModel {
    private function tableExists(): bool {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'login_attempts'");
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function recentFailures(string $email, ?string $ip, int $windowMinutes = 15): int {
        if (!$this->tableExists()) {
            return 0;
        }
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND ip_address <=> ? AND success_flag = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)');
            $stmt->execute([trim(strtolower($email)), $ip, $windowMinutes]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function record(string $email, ?string $ip, bool $success, ?int $userId = null): void {
        if (!$this->tableExists()) {
            return;
        }
        try {
            $stmt = $this->db->prepare('INSERT INTO login_attempts (company_id, user_id, email, ip_address, success_flag, created_at) VALUES (?,?,?,?,?,NOW())');
            $stmt->execute([current_company_id(), $userId, trim(strtolower($email)), $ip, $success ? 1 : 0]);
        } catch (PDOException $e) {
            // fail closed for rate-limit logging, not auth flow
        }
    }

    public function clearFailures(string $email, ?string $ip): void {
        if (!$this->tableExists()) {
            return;
        }
        try {
            $stmt = $this->db->prepare('DELETE FROM login_attempts WHERE email = ? AND ip_address <=> ? AND success_flag = 0');
            $stmt->execute([trim(strtolower($email)), $ip]);
        } catch (PDOException $e) {
            // ignore so successful auth can proceed
        }
    }

    public function clearRecentFailures(string $email, ?string $ip): void {
        $this->clearFailures($email, $ip);
    }
}
