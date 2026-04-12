<?php
namespace App\Models;

class Feature extends BaseModel {
    public function list(): array { return $this->all('feature_registry', 'category_name ASC, feature_name ASC'); }
}
