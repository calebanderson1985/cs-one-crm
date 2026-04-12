<?php
namespace App\Models;

class Document extends BaseModel {
    public function list(): array { return $this->all('documents', 'created_at DESC'); }
    public function get(int $id): ?array { return $this->find('documents', $id); }
    public function create(array $data, ?array $file): ?int {
        if (!$file || empty($file['name'])) {
            return null;
        }
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
        $relativeDir = 'uploads/company_' . current_company_id();
        $targetDir = dirname(__DIR__, 2) . '/storage/' . $relativeDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $targetName = time() . '_' . $safeName;
        $relativePath = $relativeDir . '/' . $targetName;
        $fullPath = dirname(__DIR__, 2) . '/storage/' . $relativePath;
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return null;
        }
        $stmt = $this->db->prepare('INSERT INTO documents (company_id, related_type, related_id, title, original_name, storage_path, mime_type, file_size, visibility_scope, uploaded_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute([
            current_company_id(),
            $data['related_type'] ?: null,
            $data['related_id'] ?: null,
            $data['title'] ?: $safeName,
            $file['name'],
            $relativePath,
            $file['type'] ?: 'application/octet-stream',
            $file['size'] ?? 0,
            $data['visibility_scope'] ?: 'company',
            current_user_id() ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }
    public function delete(int $id): void {
        $doc = $this->get($id);
        if ($doc && !empty($doc['storage_path'])) {
            $fullPath = dirname(__DIR__, 2) . '/storage/' . $doc['storage_path'];
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
        $this->deleteRecord('documents', $id);
    }
    public function notifyUpload(int $id, string $title): void {
        (new Notification($this->db))->create([
            'title' => 'New document uploaded',
            'message_text' => $title,
            'level_name' => 'info',
            'link_url' => 'index.php?page=documents',
        ]);
    }
}
