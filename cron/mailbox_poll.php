<?php
$root = dirname(__DIR__);
require $root . '/bootstrap.php';
if (!isset($pdo)) {
    exit("Application is not installed.\n");
}
$results = (new App\Services\MailboxPollService($pdo))->pollAll();
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
