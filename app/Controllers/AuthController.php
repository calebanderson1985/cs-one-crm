<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;

class AuthController {
    public function __construct(private \PDO $db) {}

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $model = new User($this->db);
            $user = $model->findByEmail(trim($_POST['email'] ?? ''));
            if ($user && !empty($user['is_active']) && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
                Auth::attempt($user);
                audit_log($this->db, 'auth', 'login', (int) $user['id'], 'User logged in');
                redirect('index.php');
            }
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
