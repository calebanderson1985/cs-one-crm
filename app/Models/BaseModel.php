<?php
namespace App\Models;

use PDO;

class BaseModel {
    protected PDO $db;

    protected array $tenantScopedTables = [
        'users','clients','leads','deals','communications','tasks','commissions','documents','audit_logs',
        'notifications','workflows','workflow_runs','workflow_queue','role_permissions','communication_templates',
        'outbound_messages','ai_logs','api_tokens','onboarding_steps','webhook_events','support_tickets','knowledge_base_articles','sla_policies'
    ];

    protected array $ownerColumns = [
        'clients' => 'assigned_user_id',
        'leads' => 'assigned_user_id',
        'deals' => 'owner_user_id',
        'tasks' => 'assigned_user_id',
        'commissions' => 'agent_user_id',
        'communications' => 'created_by',
        'documents' => 'uploaded_by',
        'users' => 'id',
        'notifications' => 'user_id',
        'audit_logs' => 'user_id',
        'ai_logs' => 'user_id',
        'support_tickets' => 'owner_user_id',
        'knowledge_base_articles' => 'created_by',
    ];

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    protected function all(string $table, string $order = 'id DESC'): array {
        [$where, $params] = $this->buildScope($table);
        $sql = "SELECT * FROM {$table}" . ($where ? " WHERE {$where}" : '') . " ORDER BY {$order}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function find(string $table, int $id): ?array {
        [$where, $params] = $this->buildScope($table);
        $clauses = array_filter([$where, 'id = ?']);
        $params[] = $id;
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE " . implode(' AND ', $clauses) . ' LIMIT 1');
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    protected function deleteRecord(string $table, int $id): void {
        [$where, $params] = $this->buildScope($table);
        $clauses = array_filter([$where, 'id = ?']);
        $params[] = $id;
        $stmt = $this->db->prepare("DELETE FROM {$table} WHERE " . implode(' AND ', $clauses));
        $stmt->execute($params);
    }

    protected function count(string $table): int {
        [$where, $params] = $this->buildScope($table);
        $sql = "SELECT COUNT(*) FROM {$table}" . ($where ? " WHERE {$where}" : '');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    protected function userNameById(?int $id): ?string {
        if (!$id) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT full_name FROM users WHERE id = ? AND company_id = ? LIMIT 1');
        $stmt->execute([$id, current_company_id()]);
        $name = $stmt->fetchColumn();
        return $name !== false ? (string) $name : null;
    }

    protected function isTenantScoped(string $table): bool {
        return in_array($table, $this->tenantScopedTables, true);
    }

    protected function buildScope(string $table): array {
        $clauses = [];
        $params = [];
        if ($this->isTenantScoped($table)) {
            $clauses[] = 'company_id = ?';
            $params[] = current_company_id();
        }

        $role = current_user_role();
        if (!$role || $role === 'admin') {
            return [implode(' AND ', $clauses), $params];
        }

        if ($table === 'users') {
            return $this->buildUserScope($clauses, $params);
        }

        if ($table === 'notifications') {
            if ($role === 'manager') {
                [$inSql, $inParams] = sql_in_clause(team_user_ids($this->db));
                $clauses[] = "(user_id IS NULL OR user_id IN {$inSql})";
                array_push($params, ...$inParams);
            } else {
                $clauses[] = '(user_id IS NULL OR user_id = ?)';
                $params[] = current_user_id();
            }
            return [implode(' AND ', $clauses), $params];
        }

        if ($table === 'documents') {
            if ($role === 'client') {
                $clauses[] = "visibility_scope = 'client'";
                $clauses[] = '((related_type = ? AND related_id = ?) OR uploaded_by = ?)';
                array_push($params, 'Client', current_portal_client_id(), current_user_id());
                return [implode(' AND ', $clauses), $params];
            }
            if ($role === 'manager') {
                return [implode(' AND ', $clauses), $params];
            }
            $clauses[] = "(visibility_scope = 'company' OR uploaded_by = ?)";
            $params[] = current_user_id();
            return [implode(' AND ', $clauses), $params];
        }

        if ($table === 'communications') {
            if ($role === 'client') {
                $clauses[] = '(((related_type = ? AND related_id = ?) OR recipient = ?) AND direction IN (\'Outbound\',\'Inbound\'))';
                array_push($params, 'Client', current_portal_client_id(), current_user_email());
                return [implode(' AND ', $clauses), $params];
            }
            if ($role === 'manager') {
                return [implode(' AND ', $clauses), $params];
            }
            $clauses[] = '(created_by = ? OR created_by IS NULL)';
            $params[] = current_user_id();
            return [implode(' AND ', $clauses), $params];
        }

        if (!isset($this->ownerColumns[$table])) {
            return [implode(' AND ', $clauses), $params];
        }

        $ownerColumn = $this->ownerColumns[$table];
        if ($role === 'manager') {
            [$inSql, $inParams] = sql_in_clause(team_user_ids($this->db));
            $clauses[] = "({$ownerColumn} IS NULL OR {$ownerColumn} IN {$inSql})";
            array_push($params, ...$inParams);
            return [implode(' AND ', $clauses), $params];
        }

        if ($role === 'client') {
            $clauses[] = '1 = 0';
            return [implode(' AND ', $clauses), $params];
        }

        $clauses[] = "{$ownerColumn} = ?";
        $params[] = current_user_id();
        return [implode(' AND ', $clauses), $params];
    }

    private function buildUserScope(array $clauses, array $params): array {
        $role = current_user_role();
        if ($role === 'manager') {
            [$inSql, $inParams] = sql_in_clause(team_user_ids($this->db));
            $clauses[] = "id IN {$inSql}";
            array_push($params, ...$inParams);
            return [implode(' AND ', $clauses), $params];
        }
        $clauses[] = 'id = ?';
        $params[] = current_user_id();
        return [implode(' AND ', $clauses), $params];
    }
}
