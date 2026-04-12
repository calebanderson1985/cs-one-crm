<?php
namespace App\Models;

class PasswordReset extends BaseModel {
    public function issue(string $email): ?string {
        $userModel = new User($this->db);
        $user = $userModel->findByEmail($email);
        if (!$user || empty($user['is_active'])) {
            return null;
        }
        $token = bin2hex(random_bytes(24));
        $hash = hash('sha256', $token);
        $this->db->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$user['id']]);
        $stmt = $this->db->prepare('INSERT INTO password_resets (user_id, email, token_hash, expires_at, created_at) VALUES (?,?,?,?,NOW())');
        $stmt->execute([$user['id'], $user['email'], $hash, date('Y-m-d H:i:s', time() + 3600)]);
        return $token;
    }

    public function consume(string $token, string $newPassword): bool {
        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare('SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at >= NOW() ORDER BY id DESC LIMIT 1');
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        $this->db->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')->execute([
            password_hash($newPassword, PASSWORD_DEFAULT),
            $row['user_id'],
        ]);
        $this->db->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$row['id']]);
        return true;
    }
}
