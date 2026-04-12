<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\KnowledgeBaseArticle;

class KnowledgeBaseController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('knowledge_base', 'view');
        $model = new KnowledgeBaseArticle($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                Auth::requirePermission('knowledge_base', 'create');
                $id = $model->create($_POST);
                audit_log($this->db, 'knowledge_base', 'create', $id, 'Knowledge article created');
                flash('success', 'Knowledge article created.');
            } elseif ($action === 'update') {
                Auth::requirePermission('knowledge_base', 'edit');
                $id = (int)($_POST['id'] ?? 0);
                $model->updateRecord($id, $_POST);
                audit_log($this->db, 'knowledge_base', 'update', $id, 'Knowledge article updated');
                flash('success', 'Knowledge article updated.');
            } elseif ($action === 'delete') {
                Auth::requirePermission('knowledge_base', 'delete');
                $id = (int)($_POST['id'] ?? 0);
                $model->delete($id);
                audit_log($this->db, 'knowledge_base', 'delete', $id, 'Knowledge article deleted');
                flash('success', 'Knowledge article deleted.');
            }
            redirect('index.php?page=knowledge_base');
        }
        $filters = [
            'visibility' => trim((string)($_GET['visibility'] ?? '')),
            'q' => trim((string)($_GET['q'] ?? '')),
        ];
        $articles = $model->list($filters);
        View::render('admin/knowledge_base', compact('articles', 'filters'));
    }
}
