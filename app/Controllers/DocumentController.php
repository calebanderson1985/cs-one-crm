<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Document;

class DocumentController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('documents', 'view');
        $model = new Document($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? 'upload';
            if ($action === 'upload') {
                Auth::requirePermission('documents', 'create');
                $documentId = $model->create($_POST, $_FILES['document_file'] ?? null);
                flash($documentId ? 'success' : 'error', $documentId ? 'Document uploaded.' : 'Upload failed.');
                if ($documentId) {
                    audit_log($this->db, 'documents', 'upload', $documentId, $_POST['title'] ?? 'Document uploaded');
                    $model->notifyUpload($documentId, $_POST['title'] ?? 'Document uploaded');
                }
            }
            if ($action === 'delete') {
                Auth::requirePermission('documents', 'delete');
                $id = (int) ($_POST['id'] ?? 0);
                $model->delete($id);
                audit_log($this->db, 'documents', 'delete', $id, 'Document deleted');
                flash('success', 'Document deleted.');
            }
            redirect('index.php?page=documents');
        }
        $documents = $model->list();
        View::render('documents/index', compact('documents'));
    }

    public function download(): void {
        Auth::requirePermission('documents', 'view');
        $model = new Document($this->db);
        $id = (int) ($_GET['id'] ?? 0);
        $doc = $model->get($id);
        if (!$doc) {
            http_response_code(404);
            exit('Document not found.');
        }
        $fullPath = dirname(__DIR__, 2) . '/storage/' . $doc['storage_path'];
        if (!is_file($fullPath)) {
            http_response_code(404);
            exit('Stored file is missing.');
        }
        audit_log($this->db, 'documents', 'download', $id, 'Document downloaded');
        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($doc['original_name']) . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }
}
