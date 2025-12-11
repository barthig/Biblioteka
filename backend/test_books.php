<?php

require '/app/vendor/autoload.php';

use App\Kernel;
use App\Entity\Book;
use App\Repository\BookRepository;

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
/** @var BookRepository $repo */
$repo = $em->getRepository(Book::class);

try {
    $result = $repo->searchPublic([]);
    echo 'Books found: ' . count($result['data']) . PHP_EOL;
    echo 'Total: ' . $result['meta']['total'] . PHP_EOL;
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString();
}
