<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use App\Kernel;

$kernel = new Kernel($_ENV['APP_ENV'], (bool)$_ENV['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "\n=== Checking Book->inventory Collection ===\n\n";

$book = $em->getRepository(\App\Entity\Book::class)->find(3);

echo "Book: {$book->getTitle()}\n";
echo "Book->getInventory()->count(): {$book->getInventory()->count()}\n\n";

foreach ($book->getInventory() as $copy) {
    echo "Copy ID: {$copy->getId()}, Status: {$copy->getStatus()}\n";
}

// Check database directly
$conn = $em->getConnection();
$stmt = $conn->prepare('SELECT id, status, access_type FROM book_copy WHERE book_id = :book_id');
$result = $stmt->executeQuery(['book_id' => 3]);
$rows = $result->fetchAllAssociative();

echo "\nDatabase query result:\n";
foreach ($rows as $row) {
    echo "  ID: {$row['id']}, Status: {$row['status']}, Access: {$row['access_type']}\n";
}

echo "\n";
