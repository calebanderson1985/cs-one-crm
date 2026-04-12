<?php
namespace App\Models;

class User extends BaseModel {
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? AND company_id = ? LIMIT 1');
        $stmt->execute([$email, current_company_id()]);
        return $stmt->fetch() ?: null;
    }

    public function list(): array {
        return $this->all('users', 'full_name ASC');
    }

    public function get(int $id): ?array {
        return $this->find('users', $id);
    }

    public function assigneeOptions(): array {
        $companyId = current_company_id();
        $role = current_user_role();
        $sql = 'SELECT id, full_name, role FROM users WHERE company_id = ? AND is_active = 1 AND role IN (\'admin\',\'manager\',\'agent\')';
        $params = [$companyId];
        if ($role === 'manager') {
            [$inSql, $inParams] = sql_in_clause(team_user_ids($this->db));
            $sql .= " AND id IN {$inSql}";
            array_push($params, ...$inParams);
        }
        if ($role === 'agent') {
            $sql .= ' AND id = ?';
            $params[] = current_user_id();
        }
        $sql .= ' ORDER BY full_name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function clientLinkOptions(): array {
        $stmt = $this->db->prepare('SELECT id, company_name, contact_name FROM clients WHERE company_id = ? ORDER BY company_name ASC');
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO users (company_id, full_name, email, password_hash, role, manager_user_id, portal_client_id, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            current_company_id(),
            $data['full_name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'],
            !empty($data['manager_user_id']) ? (int) $data['manager_user_id'] : null,
            !empty($data['portal_client_id']) ? (int) $data['portal_client_id'] : null,
            $data['is_active'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        if (!empty($data['password'])) {
            $stmt = $this->db->prepare('UPDATE users SET full_name=?, email=?, role=?, manager_user_id=?, portal_client_id=?, is_active=?, password_hash=?, updated_at=NOW() WHERE id=? AND company_id=?');
            $stmt->execute([
                $data['full_name'], $data['email'], $data['role'],
                !empty($data['manager_user_id']) ? (int) $data['manager_user_id'] : null,
                !empty($data['portal_client_id']) ? (int) $data['portal_client_id'] : null,
                $data['is_active'] ?? 1,
                password_hash($data['password'], PASSWORD_DEFAULT),
                $id, current_company_id(),
            ]);
            return;
        }
        $stmt = $this->db->prepare('UPDATE users SET full_name=?, email=?, role=?, manager_user_id=?, portal_client_id=?, is_active=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([
            $data['full_name'], $data['email'], $data['role'],
            !empty($data['manager_user_id']) ? (int) $data['manager_user_id'] : null,
            !empty($data['portal_client_id']) ? (int) $data['portal_client_id'] : null,
            $data['is_active'] ?? 1,
            $id, current_company_id(),
        ]);
    }

    public function delete(int $id): void {
        $this->deleteRecord('users', $id);
    }
}
