<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\Notification;
use App\Models\PasswordReset;

class PasswordController {
    public function __construct(private \PDO $db) {}

    public function forgot(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $email = trim($_POST['email'] ?? '');
            $token = (new PasswordReset($this->db))->issue($email);
            if ($token) {
                $resetLink = 'index.php?page=reset_password&token=' . urlencode($token);
                (new Notification($this->db))->create([
                    'title' => 'Password reset requested',
                    'message_text' => 'A password reset was requested for ' . $email,
                    'level_name' => 'warning',
                    'link_url' => $resetLink,
                ]);
                $message = 'Password reset token created. Demo reset link: ' . $resetLink;
            } else {
                $message = 'If the account exists, a reset link would be issued.';
            }
            View::render('auth/forgot_password', compact('message'));
            return;
        }
        View::render('auth/forgot_password');
    }

    public function reset(): void {
        $token = trim($_GET['token'] ?? $_POST['token'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirm'] ?? '';
            if ($password === '' || $password !== $confirm) {
                $error = 'Passwords do not match.';
                View::render('auth/reset_password', compact('token', 'error'));
                return;
            }
            $policyErrors = password_policy_errors($this->db, $password);
            if ($policyErrors) {
                $error = implode(' ', $policyErrors);
                View::render('auth/reset_password', compact('token', 'error'));
                return;
            }
            $ok = (new PasswordReset($this->db))->consume($token, $password);
            if ($ok) {
                flash('success', 'Password reset completed. You can now sign in.');
                redirect('index.php?page=login');
            }
            $error = 'Reset link is invalid or expired.';
            View::render('auth/reset_password', compact('token', 'error'));
            return;
        }
        View::render('auth/reset_password', compact('token'));
    }
}
