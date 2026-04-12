<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\OnboardingItem;

class OnboardingController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('onboarding', 'view');
        $model = new OnboardingItem($this->db);
        $model->ensureDefaults();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('onboarding', 'create');
                $id = $model->create($_POST);
                audit_log($this->db, 'onboarding', 'create', $id, 'Onboarding item created');
                flash('success', 'Checklist item created.');
            }
            if ($action === 'update') {
                Auth::requirePermission('onboarding', 'edit');
                $id = (int) $_POST['id'];
                $model->update($id, $_POST);
                audit_log($this->db, 'onboarding', 'update', $id, 'Onboarding item updated');
                flash('success', 'Checklist item updated.');
            }
            if ($action === 'toggle') {
                Auth::requirePermission('onboarding', 'edit');
                $id = (int) $_POST['id'];
                $model->toggleComplete($id, !empty($_POST['is_complete']));
                audit_log($this->db, 'onboarding', 'toggle', $id, 'Onboarding item status changed');
                flash('success', 'Checklist progress updated.');
            }
            if ($action === 'delete') {
                Auth::requirePermission('onboarding', 'delete');
                $id = (int) $_POST['id'];
                $model->delete($id);
                audit_log($this->db, 'onboarding', 'delete', $id, 'Onboarding item deleted');
                flash('success', 'Checklist item deleted.');
            }
            redirect('index.php?page=onboarding');
        }

        $items = $model->list();
        $editItem = request_id() ? $model->get(request_id()) : null;
        $completionPercent = $model->completionPercent();
        View::render('admin/onboarding', compact('items', 'editItem', 'completionPercent'));
    }
}
