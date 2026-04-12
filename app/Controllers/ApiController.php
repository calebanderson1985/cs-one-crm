<?php
namespace App\Controllers;

use App\Models\Client;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Task;

class ApiController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        $token = $_GET['token'] ?? ($_SERVER['HTTP_X_API_KEY'] ?? '');
        $this->resolveCompanyFromToken((string) $token);
        $expected = setting($this->db, 'api_token', 'change-me');
        if (!$token || !hash_equals((string) $expected, (string) $token)) {
            json_response(['error' => 'Unauthorized'], 401);
        }

        $resource = $_GET['resource'] ?? trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
        $id = (int) ($_GET['id'] ?? 0);
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $input = $method === 'GET' ? $_GET : (parse_json_input() ?: $_POST);

        [$model, $module] = $this->resolveResource($resource);
        if (!$model) {
            json_response(['error' => 'Unknown resource'], 404);
        }

        if (in_array($method, ['PUT', 'PATCH'], true) && !$id && !empty($input['id'])) {
            $id = (int) $input['id'];
        }

        try {
            $payload = match ($method) {
                'GET' => $id ? ['data' => $model->get($id)] : ['data' => $model->list()],
                'POST' => ['data' => ['id' => $model->create($input)]],
                'PUT', 'PATCH' => $this->updateResource($model, $id, $input),
                'DELETE' => $this->deleteResource($model, $id),
                default => throw new \RuntimeException('Method not allowed', 405),
            };
            audit_log($this->db, 'api', strtolower($method), $id ?: null, 'API ' . strtolower($method) . ' for ' . $module);
            $payload['meta'] = [
                'resource' => $resource,
                'method' => $method,
                'generated_at' => date('c'),
                'company_id' => current_company_id(),
            ];
            json_response($payload);
        } catch (\RuntimeException $e) {
            json_response(['error' => $e->getMessage()], $e->getCode() >= 400 ? $e->getCode() : 400);
        }
    }

    private function resolveCompanyFromToken(string $token): void {
        if ($token === '') {
            return;
        }
        if (!empty($_SESSION['user']['company_id']) || !empty($_SERVER['HTTP_X_COMPANY_ID']) || !empty($_GET['company_id'])) {
            return;
        }
        $stmt = $this->db->prepare("SELECT company_id FROM system_settings WHERE setting_key = 'api_token' AND setting_value = ? ORDER BY company_id ASC LIMIT 1");
        $stmt->execute([$token]);
        $companyId = (int) $stmt->fetchColumn();
        if ($companyId > 0) {
            $_SERVER['HTTP_X_COMPANY_ID'] = (string) $companyId;
        }
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

    private function updateResource(object $model, int $id, array $input): array {
        if (!$id) {
            throw new \RuntimeException('Missing id for update', 400);
        }
        $model->update($id, $input);
        return ['data' => $model->get($id)];
    }

    private function deleteResource(object $model, int $id): array {
        if (!$id) {
            throw new \RuntimeException('Missing id for delete', 400);
        }
        $model->delete($id);
        return ['data' => ['deleted' => true, 'id' => $id]];
    }
}
