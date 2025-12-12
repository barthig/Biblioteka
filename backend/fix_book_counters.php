<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use App\Kernel;

$kernel = new Kernel($_ENV['APP_ENV'], (bool)$_ENV['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "\n=== Fixing All Book Counters ===\n\n";

$books = $em->getRepository(\App\Entity\Book::class)->findAll();

foreach ($books as $book) {
    $beforeCopies = $book->getCopies();
    $beforeTotal = $book->getTotalCopies();
    
    $book->recalculateInventoryCounters();
    
    $afterCopies = $book->getCopies();
    $afterTotal = $book->getTotalCopies();
    
    if ($beforeCopies !== $afterCopies || $beforeTotal !== $afterTotal) {
        echo "Book ID {$book->getId()}: {$beforeCopies}/{$beforeTotal} → {$afterCopies}/{$afterTotal}\n";
    }
}

$em->flush();

echo "\n✅ All book counters fixed!\n\n";
