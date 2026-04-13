<?php
namespace App\Models;

class KnowledgeBaseArticle extends BaseModel {
    public function list(array $filters = []): array {
        [$where, $params] = $this->buildScope('knowledge_base_articles');
        $clauses = [];
        if ($where) { $clauses[] = $where; }
        if (current_user_role() === 'client') {
            $clauses[] = "visibility_scope = 'client' AND is_published = 1";
        }
        if (!empty($filters['visibility'])) { $clauses[] = 'visibility_scope = ?'; $params[] = $filters['visibility']; }
        if (!empty($filters['q'])) { $clauses[] = '(title LIKE ? OR body_text LIKE ? OR category_name LIKE ?)'; $params[] = '%' . $filters['q'] . '%'; $params[] = '%' . $filters['q'] . '%'; $params[] = '%' . $filters['q'] . '%'; }
        $sql = 'SELECT * FROM knowledge_base_articles' . ($clauses ? ' WHERE ' . implode(' AND ', $clauses) : '') . ' ORDER BY is_published DESC, updated_at DESC, id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }



    public function listClientVisible(): array {
        $stmt = $this->db->prepare("SELECT * FROM knowledge_base_articles WHERE company_id = ? AND visibility_scope = 'client' AND is_published = 1 ORDER BY category_name ASC, title ASC");
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function categorySummary(array $filters = []): array {
        $clauses = ['company_id = ?'];
        $params = [current_company_id()];
        if (current_user_role() === 'client') {
            $clauses[] = "visibility_scope = 'client' AND is_published = 1";
        }
        if (!empty($filters['q'])) {
            $clauses[] = '(title LIKE ? OR body_text LIKE ? OR category_name LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        $stmt = $this->db->prepare('SELECT category_name, COUNT(*) AS total FROM knowledge_base_articles WHERE ' . implode(' AND ', $clauses) . ' GROUP BY category_name ORDER BY total DESC, category_name ASC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO knowledge_base_articles (company_id, title, category_name, visibility_scope, body_text, is_published, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([current_company_id(), trim((string)($data['title'] ?? '')), trim((string)($data['category_name'] ?? 'General')), trim((string)($data['visibility_scope'] ?? 'internal')), trim((string)($data['body_text'] ?? '')), !empty($data['is_published']) ? 1 : 0, current_user_id() ?: null]);
        return (int) $this->db->lastInsertId();
    }

    public function updateRecord(int $id, array $data): void {
        $stmt = $this->db->prepare('UPDATE knowledge_base_articles SET title = ?, category_name = ?, visibility_scope = ?, body_text = ?, is_published = ?, updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([trim((string)($data['title'] ?? '')), trim((string)($data['category_name'] ?? 'General')), trim((string)($data['visibility_scope'] ?? 'internal')), trim((string)($data['body_text'] ?? '')), !empty($data['is_published']) ? 1 : 0, $id, current_company_id()]);
    }

    public function delete(int $id): void {
        $this->deleteRecord('knowledge_base_articles', $id);
    }
}
