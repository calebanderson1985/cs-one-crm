<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Feature;

class AdminController {
    public function __construct(private \PDO $db) {}
    public function features(): void {
        Auth::requirePermission('features', 'view');
        $features = (new Feature($this->db))->list();
        View::render('admin/features', compact('features'));
    }
}
