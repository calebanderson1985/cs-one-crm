<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\ApiToken;

class ApiTokenController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('tokens', 'view');
        $model = new ApiToken($this->db);
        $revealedToken = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('tokens', 'create');
                $revealedToken = $model->createToken($_POST);
                audit_log($this->db, 'tokens', 'create', $revealedToken['id'], 'Scoped API token created');
                flash('success', 'Token created. Copy it now; it will only be shown once.');
            } elseif ($action === 'rotate') {
                Auth::requirePermission('tokens', 'edit');
                $revealedToken = $model->rotate((int)($_POST['id'] ?? 0));
                if ($revealedToken) {
                    audit_log($this->db, 'tokens', 'rotate', $revealedToken['id'], 'Scoped API token rotated');
                    flash('success', 'Token rotated. Copy the new token now; it will only be shown once.');
                }
            } elseif ($action === 'revoke') {
                Auth::requirePermission('tokens', 'delete');
                $id = (int)($_POST['id'] ?? 0);
                $model->revoke($id);
                audit_log($this->db, 'tokens', 'revoke', $id, 'Scoped API token revoked');
                flash('success', 'Token revoked.');
            }
        }

        $tokens = $model->list();
        View::render('admin/tokens', compact('tokens', 'revealedToken'));
    }
}
