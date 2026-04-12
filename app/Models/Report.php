<?php
namespace App\Models;

class Report extends BaseModel {
    public function list(): array { return $this->all('reports', 'created_at DESC'); }
}
