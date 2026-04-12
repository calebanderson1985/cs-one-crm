<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\LoginAttempt;
use App\Models\User;

class AuthController {
    public function __construct(private \PDO $db) {}

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $attempts = new LoginAttempt($this->db);
            $limit = max(3, (int)setting($this->db, 'login_rate_limit', '5'));
            $window = max(5, (int)setting($this->db, 'login_rate_window_minutes', '15'));
            $ip = client_ip();

            if ($attempts->recentFailures($email, $ip, $window) >= $limit) {
                $error = 'Too many failed login attempts. Please wait before trying again.';
                View::render('auth/login', compact('error'));
                return;
            }

            $model = new User($this->db);
            $user = $model->findByEmail($email);
            if ($user && !empty($user['is_active']) && password_verify($password, $user['password_hash'])) {
                $attempts->record($email, $ip, true, (int)$user['id']);
                $attempts->clearFailures($email, $ip);
                Auth::attempt($user);
                audit_log($this->db, 'auth', 'login', (int) $user['id'], 'User logged in');
                redirect('index.php');
            }

            $attempts->record($email, $ip, false, $user ? (int)$user['id'] : null);
            audit_log($this->db, 'auth', 'login_failed', $user ? (int)$user['id'] : null, 'Failed login attempt for ' . $email);
            $error = 'Invalid credentials or inactive user.';
            View::render('auth/login', compact('error'));
            return;
        }
        View::render('auth/login');
    }

    public function logout(): void {
        audit_log($this->db, 'auth', 'logout', current_user_id() ?: null, 'User logged out');
        Auth::logout();
        redirect('index.php?page=login');
    }
}
