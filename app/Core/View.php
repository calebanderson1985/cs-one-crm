<?php
namespace App\Core;

class View {
    public static function render(string $view, array $data = []): void {
        extract($data);
        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';
        include dirname(__DIR__) . '/Views/layouts/app.php';
    }
}
