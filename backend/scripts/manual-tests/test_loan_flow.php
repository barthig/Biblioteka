<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use App\Kernel;
use App\Application\Command\Loan\CreateLoanCommand;
use App\Application\Command\Loan\ReturnLoanCommand;
use Symfony\Component\Messenger\Stamp\HandledStamp;

$kernel = new Kernel($_ENV['APP_ENV'], (bool)$_ENV['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$commandBus = $container->get('messenger.default_bus');
$em = $container->get('doctrine')->getManager();

echo "\n=== Testing Loan Creation Flow ===\n\n";

// Get test data
$user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'user3@example.com']);
$book = $em->getRepository(\App\Entity\Book::class)->find(35);

if (!$user || !$book) {
    echo "[ERR] Test data not found\n";
    exit(1);
}

echo "User: {$user->getEmail()} (ID: {$user->getId()})\n";
echo "Book: {$book->getTitle()} (ID: {$book->getId()})\n";
echo "Available copies BEFORE: {$book->getCopies()}/{$book->getTotalCopies()}\n\n";

// Test 1: Create loan
echo "1. Creating loan...\n";
$createCommand = new CreateLoanCommand(
    userId: $user->getId(),
    bookId: $book->getId(),
    reservationId: null,
    bookCopyId: null
);

try {
    $envelope = $commandBus->dispatch($createCommand);
    $loan = $envelope->last(HandledStamp::class)->getResult();
    
    echo "[OK] Loan created successfully!\n";
    echo "   Loan ID: {$loan->getId()}\n";
    echo "   Due date: {$loan->getDueAt()->format('Y-m-d H:i:s')}\n";
    echo "   Book copy: {$loan->getBookCopy()->getId()}\n";
    
    // Refresh book to get updated counters
    $em->refresh($book);
    echo "   Available copies AFTER: {$book->getCopies()}/{$book->getTotalCopies()}\n\n";
    
    // Test 2: Return loan
    echo "2. Returning loan...\n";
    $returnCommand = new ReturnLoanCommand(
        loanId: $loan->getId(),
        userId: $user->getId()
    );
    
    $envelope = $commandBus->dispatch($returnCommand);
    $returnedLoan = $envelope->last(HandledStamp::class)->getResult();
    
    echo "[OK] Loan returned successfully!\n";
    echo "   Returned at: {$returnedLoan->getReturnedAt()->format('Y-m-d H:i:s')}\n";
    
    // Refresh book to get updated counters
    $em->refresh($book);
    echo "   Available copies AFTER RETURN: {$book->getCopies()}/{$book->getTotalCopies()}\n\n";
    
    echo "[OK] All tests passed!\n\n";
    
} catch (\Exception $e) {
    echo "[ERR] Error: {$e->getMessage()}\n";
    echo "   Trace: {$e->getTraceAsString()}\n";
    exit(1);
}
