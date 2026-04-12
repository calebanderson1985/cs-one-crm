<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

class PortalController {
    public function __construct(private \PDO $db) {}
    public function index(): void {
        Auth::requirePermission('portals', 'view');
        $user = Auth::user();
        $portalCards = [
            'admin' => ['System administration', 'Security and permission governance', 'Tenant settings and API controls'],
            'manager' => ['Team pipeline oversight', 'Workflow monitoring', 'Performance reporting'],
            'agent' => ['Lead follow-up', 'Client management', 'Task execution and communication'],
            'client' => ['View communications', 'Download portal documents', 'Review service updates'],
        ];
        View::render('portals/index', compact('user', 'portalCards'));
    }
}
