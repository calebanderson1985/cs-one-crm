<?php
$app = require __DIR__ . '/config/app.php';
session_name($app['session_name']);
session_start();

require __DIR__ . '/app/Core/helpers.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$configFile = __DIR__ . '/config/database.php';
if (file_exists($configFile)) {
    $dbConfig = require $configFile;
    $pdo = App\Core\Database::connect($dbConfig);
    $timezone = setting($pdo, 'default_timezone', 'America/Chicago');
    if ($timezone) {
        @date_default_timezone_set($timezone);
    }
}
