<?php
namespace App\Models;

class LoginAttempt extends BaseModel {
    public function recentFailures(string $email, ?string $ip, int $windowMinutes = 15): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND ip_address <=> ? AND success_flag = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        $stmt->execute([trim(strtolower($email)), $ip, $windowMinutes]);
        return (int)$stmt->fetchColumn();
    }

    public function record(string $email, ?string $ip, bool $success, ?int $userId = null): void {
        $stmt = $this->db->prepare('INSERT INTO login_attempts (company_id, user_id, email, ip_address, success_flag, created_at) VALUES (?,?,?,?,?,NOW())');
        $stmt->execute([current_company_id(), $userId, trim(strtolower($email)), $ip, $success ? 1 : 0]);
    }

    public function clearFailures(string $email, ?string $ip): void {
        $stmt = $this->db->prepare('DELETE FROM login_attempts WHERE email = ? AND ip_address <=> ? AND success_flag = 0');
        $stmt->execute([trim(strtolower($email)), $ip]);
    }
}
