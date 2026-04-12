<?php
require dirname(__DIR__) . '/bootstrap.php';

if (!isset($pdo)) {
    exit('Application is not installed yet. Open /install.php first.');
}

use App\Controllers\AdminController;
use App\Controllers\AiController;
use App\Controllers\ApiTokenController;
use App\Controllers\OnboardingController;
use App\Controllers\ApiAnalyticsController;
use App\Controllers\ApiController;
use App\Controllers\AuditController;
use App\Controllers\CompanySwitchController;
use App\Controllers\AuthController;
use App\Controllers\ClientController;
use App\Controllers\CommissionController;
use App\Controllers\CommunicationController;
use App\Controllers\DashboardController;
use App\Controllers\DealController;
use App\Controllers\DocumentController;
use App\Controllers\LeadController;
use App\Controllers\NotificationController;
use App\Controllers\PasswordController;
use App\Controllers\PermissionController;
use App\Controllers\PortalController;
use App\Controllers\ReportController;
use App\Controllers\SettingsController;
use App\Controllers\TaskController;
use App\Controllers\UserController;
use App\Controllers\WorkflowController;
use App\Controllers\WebhookEventController;
use App\Controllers\QueueOpsController;
use App\Controllers\DiagnosticsController;

$page = $_GET['page'] ?? 'dashboard';

$routes = [
    'login' => [AuthController::class, 'login'],
    'forgot_password' => [PasswordController::class, 'forgot'],
    'reset_password' => [PasswordController::class, 'reset'],
    'logout' => [AuthController::class, 'logout'],
    'dashboard' => [DashboardController::class, 'index'],
    'clients' => [ClientController::class, 'index'],
    'leads' => [LeadController::class, 'index'],
    'deals' => [DealController::class, 'index'],
    'communications' => [CommunicationController::class, 'index'],
    'documents' => [DocumentController::class, 'index'],
    'document_download' => [DocumentController::class, 'download'],
    'tasks' => [TaskController::class, 'index'],
    'commissions' => [CommissionController::class, 'index'],
    'reports' => [ReportController::class, 'index'],
    'workflows' => [WorkflowController::class, 'index'],
    'audit' => [AuditController::class, 'index'],
    'api' => [ApiController::class, 'index'],
    'notifications' => [NotificationController::class, 'index'],
    'portals' => [PortalController::class, 'index'],
    'features' => [AdminController::class, 'features'],
    'settings' => [SettingsController::class, 'index'],
    'users' => [UserController::class, 'index'],
    'permissions' => [PermissionController::class, 'index'],
    'ai' => [AiController::class, 'index'],
    'tokens' => [ApiTokenController::class, 'index'],
    'onboarding' => [OnboardingController::class, 'index'],
    'api_analytics' => [ApiAnalyticsController::class, 'index'],
    'company_switch' => [CompanySwitchController::class, 'index'],
    'webhooks' => [WebhookEventController::class, 'index'],
    'queue_ops' => [QueueOpsController::class, 'index'],
    'diagnostics' => [DiagnosticsController::class, 'index'],
];

if (!isset($routes[$page])) {
    http_response_code(404);
    exit('Page not found');
}

[$class, $method] = $routes[$page];
$controller = new $class($pdo);
$controller->$method();
