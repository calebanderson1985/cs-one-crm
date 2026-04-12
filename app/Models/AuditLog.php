<?php
namespace App\Models;

class AuditLog extends BaseModel {
    public function list(): array { return $this->all('audit_logs', 'created_at DESC'); }
}
