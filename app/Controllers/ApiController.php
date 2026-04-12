<?php
namespace App\Controllers;

use App\Models\ApiRequestLog;
use App\Models\ApiToken;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Task;

class ApiController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        if ($this->isUiRequest()) {
            $base = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $example = $base . dirname($_SERVER['SCRIPT_NAME'] ?? '/public/index.php') . '/api.php?resource=clients';
            echo '<!doctype html><html><head><meta charset="utf-8"><link rel="stylesheet" href="assets/css/app.css"><title>API</title></head><body><main class="content"><div class="card"><h2>API</h2><p>Use scoped bearer tokens from the API Token Center, or the legacy single token for backward compatibility.</p><div class="code-block">GET ' . htmlspecialchars($example, ENT_QUOTES, 'UTF-8') . '
Authorization: Bearer YOUR_TOKEN</div><p><a href="index.php?page=tokens">Manage tokens</a></p></div></main></body></html>';
            return;
        }

        $resource = $_GET['resource'] ?? trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
        $id = (int) ($_GET['id'] ?? 0);
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $input = $method === 'GET' ? $_GET : (parse_json_input() ?: $_POST);
        $operation = $method === 'GET' ? 'read' : 'write';

        $tokenInfo = $this->authenticateToken($resource, $operation);
        if (!empty($tokenInfo['company_id']) && empty($_SERVER['HTTP_X_COMPANY_ID'])) {
            $_SERVER['HTTP_X_COMPANY_ID'] = (string) $tokenInfo['company_id'];
        }

        [$model, $module] = $this->resolveResource((string)$resource);
        if (!$model) {
            json_response(['error' => 'Unknown resource'], 404);
        }

        if (in_array($method, ['PUT', 'PATCH'], true) && !$id && !empty($input['id'])) {
            $id = (int) $input['id'];
        }

        $input = $this->filterInput((string)$resource, $input);

        try {
            $payload = match ($method) {
                'GET' => $id ? ['data' => $model->get($id)] : ['data' => $model->list()],
                'POST' => ['data' => ['id' => $model->create($input)]],
                'PUT', 'PATCH' => $this->updateResource($model, $id, $input),
                'DELETE' => $this->deleteResource($model, $id),
                default => throw new \RuntimeException('Method not allowed', 405),
            };
            audit_log($this->db, 'api', strtolower($method), $id ?: null, 'API ' . strtolower($method) . ' for ' . $module);
            (new ApiRequestLog($this->db))->log([
                'api_token_id' => $tokenInfo['token_id'] ?? null,
                'resource_name' => $resource,
                'http_method' => $method,
                'status_code' => 200,
                'request_path' => $_SERVER['REQUEST_URI'] ?? '/api.php',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'scope_text' => $tokenInfo['scopes'] ?? '',
            ]);
            $payload['meta'] = [
                'resource' => $resource,
                'method' => $method,
                'generated_at' => date('c'),
                'company_id' => current_company_id(),
                'auth_mode' => $tokenInfo['mode'],
                'scopes' => $tokenInfo['scopes'],
            ];
            json_response($payload);
        } catch (\RuntimeException $e) {
            json_response(['error' => $e->getMessage()], $e->getCode() >= 400 ? $e->getCode() : 400);
        }
    }

    private function isUiRequest(): bool {
        return isset($_GET['page']) || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false && empty($_GET['resource']) && empty($_SERVER['PATH_INFO']));
    }

    private function authenticateToken(string $resource, string $operation): array {
        $token = $this->extractToken();
        $scopes = [];
        if ($token !== '') {
            $apiTokens = new ApiToken($this->db);
            $row = $apiTokens->findActiveByPlainText($token);
            if ($row) {
                if (!$apiTokens->allows($row, $resource, $operation)) {
                    json_response(['error' => 'Token scope does not allow this operation'], 403);
                }
                $apiTokens->touchUsage((int)$row['id']);
                return ['mode' => 'scoped_token', 'company_id' => (int)$row['company_id'], 'scopes' => (string)$row['scope_text'], 'token_id' => (int)$row['id']];
            }
        }

        $legacyToken = $_GET['token'] ?? ($_SERVER['HTTP_X_API_KEY'] ?? '');
        $this->resolveCompanyFromLegacyToken((string)$legacyToken);
        $expected = setting($this->db, 'api_token', 'change-me');
        if ($legacyToken && hash_equals((string)$expected, (string)$legacyToken)) {
            return ['mode' => 'legacy_token', 'company_id' => current_company_id(), 'scopes' => '*', 'token_id' => null];
        }
        json_response(['error' => 'Unauthorized'], 401);
    }

    private function extractToken(): string {
        $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            return trim($m[1]);
        }
        return (string)($_SERVER['HTTP_X_API_KEY'] ?? '');
    }

    private function resolveCompanyFromLegacyToken(string $token): void {
        if ($token === '') { return; }
        if (!empty($_SESSION['user']['company_id']) || !empty($_SERVER['HTTP_X_COMPANY_ID']) || !empty($_GET['company_id'])) { return; }
        $stmt = $this->db->prepare("SELECT company_id FROM system_settings WHERE setting_key = 'api_token' AND setting_value = ? ORDER BY company_id ASC LIMIT 1");
        $stmt->execute([$token]);
        $companyId = (int)$stmt->fetchColumn();
        if ($companyId > 0) { $_SERVER['HTTP_X_COMPANY_ID'] = (string)$companyId; }
    }

    private function resolveResource(string $resource): array {
        return match ($resource) {
            'clients' => [new Client($this->db), 'clients'],
            'leads' => [new Lead($this->db), 'leads'],
            'deals' => [new Deal($this->db), 'deals'],
            'tasks' => [new Task($this->db), 'tasks'],
            'communications' => [new Message($this->db), 'communications'],
            default => [null, null],
        };
    }

    private function filterInput(string $resource, array $input): array {
        $allowed = [
            'clients' => ['company_name','contact_name','email','phone','status','assigned_user_id','assigned_agent','notes'],
            'leads' => ['lead_name','company_name','email','phone','source_name','stage','assigned_user_id','assigned_to','ai_score','notes'],
            'deals' => ['deal_name','client_name','stage','amount','owner_user_id','owner_name','close_date','notes'],
            'tasks' => ['task_name','related_type','related_name','assigned_user_id','assigned_to','priority_level','due_date','status','notes'],
            'communications' => ['related_type','related_id','channel','direction','recipient','subject_line','body_text','status','provider_name','template_id'],
        ][$resource] ?? [];
        return array_intersect_key($input, array_flip($allowed));
    }

    private function updateResource(object $model, int $id, array $input): array {
        if (!$id) { throw new \RuntimeException('Missing id for update', 400); }
        $model->update($id, $input);
        return ['data' => $model->get($id)];
    }

    private function deleteResource(object $model, int $id): array {
        if (!$id) { throw new \RuntimeException('Missing id for delete', 400); }
        $model->delete($id);
        return ['data' => ['deleted' => true, 'id' => $id]];
    }
}
