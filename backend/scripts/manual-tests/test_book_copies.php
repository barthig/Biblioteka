<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use App\Kernel;

$kernel = new Kernel($_ENV['APP_ENV'], (bool)$_ENV['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "\n=== Checking Book Copies Status ===\n\n";

$copies = $em->getRepository(\App\Entity\BookCopy::class)->findBy(['book' => 3]);

echo "Book ID 3 has " . count($copies) . " copies:\n\n";

foreach ($copies as $copy) {
    echo "Copy ID: {$copy->getId()}\n";
    echo "  Status: {$copy->getStatus()}\n";
    echo "  Access Type: {$copy->getAccessType()}\n";
    echo "  Condition: {$copy->getConditionState()}\n\n";
}

// Check if any are available
$available = $em->getRepository(\App\Entity\BookCopy::class)->findBy([
    'book' => 3,
    'status' => 'available'
]);

echo "Available copies: " . count($available) . "\n";

// Check book counters
$book = $em->getRepository(\App\Entity\Book::class)->find(3);
echo "\nBook counters:\n";
echo "  copies (available): {$book->getCopies()}\n";
echo "  totalCopies: {$book->getTotalCopies()}\n";

// Recalculate
$book->recalculateInventoryCounters();
$em->persist($book);
$em->flush();

echo "\nAfter recalculation:\n";
echo "  copies (available): {$book->getCopies()}\n";
echo "  totalCopies: {$book->getTotalCopies()}\n\n";
