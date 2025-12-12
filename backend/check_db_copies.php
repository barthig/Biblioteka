<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use App\Kernel;

$kernel = new Kernel($_ENV['APP_ENV'], (bool)$_ENV['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$conn = $em->getConnection();

echo "\n=== Checking Database Book_Copy Counts ===\n\n";

$stmt = $conn->prepare('SELECT book_id, COUNT(*) as copy_count FROM book_copy GROUP BY book_id ORDER BY book_id LIMIT 10');
$result = $stmt->executeQuery();
$rows = $result->fetchAllAssociative();

foreach ($rows as $row) {
    echo "Book ID {$row['book_id']}: {$row['copy_count']} copies in database\n";
}

echo "\n";
