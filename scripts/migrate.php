<?php
if (PHP_SAPI !== 'cli') { exit("Run from CLI only.
"); }
$root = dirname(__DIR__);
$config = require $root . '/config/database.php';
require $root . '/app/Core/Database.php';
$pdo = App\Core\Database::connect($config);
$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (id INT AUTO_INCREMENT PRIMARY KEY, migration_name VARCHAR(190) NOT NULL UNIQUE, ran_at DATETIME NOT NULL)');
$ran = $pdo->query('SELECT migration_name FROM migrations')->fetchAll(PDO::FETCH_COLUMN) ?: [];
$files = glob($root . '/database/migrations/*.sql');
sort($files);
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $ran, true)) { echo "Skipping $name
"; continue; }
    echo "Running $name
";
    $pdo->exec(file_get_contents($file));
    $stmt = $pdo->prepare('INSERT INTO migrations (migration_name, ran_at) VALUES (?, NOW())');
    $stmt->execute([$name]);
}
echo "Done.
";
