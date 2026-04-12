<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\OnboardingStep;

class OnboardingController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('onboarding', 'view');
        $model = new OnboardingStep($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            Auth::requirePermission('onboarding', 'edit');
            $id = (int)($_POST['id'] ?? 0);
            $complete = !empty($_POST['is_complete']);
            $model->complete($id, $complete);
            audit_log($this->db, 'onboarding', $complete ? 'complete' : 'reopen', $id, 'Launch wizard step updated');
            flash('success', 'Launch checklist updated.');
            redirect('index.php?page=onboarding');
        }
        $steps = $model->list();
        $progress = $model->progress();
        View::render('admin/onboarding', compact('steps', 'progress'));
    }
}
