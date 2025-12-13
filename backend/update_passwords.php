<?php
require __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$connection = DriverManager::getConnection([
    'url' => 'postgresql://biblioteka:biblioteka@db:5432/biblioteka_dev',
    'driver' => 'pdo_pgsql',
]);

$hash = password_hash('admin', PASSWORD_BCRYPT);
echo "Generated hash: $hash\n";

$updated = $connection->executeStatement(
    'UPDATE app_user SET password = ?',
    [$hash]
);

echo "Updated $updated users\n";

// Verify
$users = $connection->fetchAllAssociative(
    'SELECT id, email, substring(password, 1, 20) as pass_start FROM app_user ORDER BY id LIMIT 5'
);

foreach ($users as $user) {
    echo sprintf("ID %d: %s => %s\n", $user['id'], $user['email'], $user['pass_start']);
}
