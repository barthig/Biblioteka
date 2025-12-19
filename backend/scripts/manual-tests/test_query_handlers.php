<?php
// Quick manual smoke test for Doctrine query handlers in the library system.

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Bootstrap Symfony
$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

/** @var \Doctrine\ORM\EntityManagerInterface $em */
$em = $container->get('doctrine.orm.entity_manager');

// Get repositories
$loanRepo = $em->getRepository(\App\Entity\Loan::class);
$favoriteRepo = $em->getRepository(\App\Entity\Favorite::class);
$reservationRepo = $em->getRepository(\App\Entity\Reservation::class);
$reviewRepo = $em->getRepository(\App\Entity\Review::class);

echo "\n=== TESTING QUERY HANDLERS ===\n\n";

// Test 1: List User Loans (user ID = 1)
echo "1. Testing LoanRepository->findByUser(userId=1)...\n";
try {
    $qb = $loanRepo->createQueryBuilder('l')
        ->leftJoin('l.user', 'u')->addSelect('u')
        ->leftJoin('l.book', 'b')->addSelect('b')
        ->leftJoin('l.bookCopy', 'bc')->addSelect('bc')
        ->where('l.user = :userId')
        ->setParameter('userId', 1)
        ->orderBy('l.borrowedAt', 'DESC')
        ->setMaxResults(5);

    $loans = $qb->getQuery()->getResult();

    echo "   OK - query executed successfully\n";
    echo "   Found: " . count($loans) . " loans\n";

    if (count($loans) > 0) {
        echo "   Sample loan:\n";
        $loan = $loans[0];
        echo "     ID: {$loan->getId()}\n";
        echo "     Book: {$loan->getBook()->getTitle()}\n";
        echo "     User: {$loan->getUser()->getEmail()}\n";
        echo "     Borrowed: {$loan->getBorrowedAt()->format('Y-m-d H:i:s')}\n";
        echo "     Due: {$loan->getDueAt()->format('Y-m-d H:i:s')}\n";
        echo "     Returned: " . ($loan->getReturnedAt() ? $loan->getReturnedAt()->format('Y-m-d H:i:s') : 'NULL') . "\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    if (strpos($e->getMessage(), 'SQLSTATE') !== false) {
        echo "   SQL ERROR DETAILS:\n";
        echo "   " . ($e->getPrevious() ? $e->getPrevious()->getMessage() : '') . "\n";
    }
}

// Test 2: List User Favorites
echo "\n2. Testing FavoriteRepository->findByUser(userId=1)...\n";
try {
    $qb = $favoriteRepo->createQueryBuilder('f')
        ->leftJoin('f.book', 'b')->addSelect('b')
        ->leftJoin('f.user', 'u')->addSelect('u')
        ->where('f.user = :userId')
        ->setParameter('userId', 1)
        ->setMaxResults(5);

    $favorites = $qb->getQuery()->getResult();

    echo "   OK - query executed successfully\n";
    echo "   Found: " . count($favorites) . " favorites\n";

    if (count($favorites) > 0) {
        $fav = $favorites[0];
        echo "   Sample favorite:\n";
        echo "     Book: {$fav->getBook()->getTitle()}\n";
        echo "     User: {$fav->getUser()->getEmail()}\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 3: List User Reservations
echo "\n3. Testing ReservationRepository->findByUser(userId=1)...\n";
try {
    $qb = $reservationRepo->createQueryBuilder('r')
        ->leftJoin('r.book', 'b')->addSelect('b')
        ->leftJoin('r.user', 'u')->addSelect('u')
        ->where('r.user = :userId')
        ->setParameter('userId', 1)
        ->setMaxResults(5);

    $reservations = $qb->getQuery()->getResult();

    echo "   OK - query executed successfully\n";
    echo "   Found: " . count($reservations) . " reservations\n";

    if (count($reservations) > 0) {
        $res = $reservations[0];
        echo "   Sample reservation:\n";
        echo "     Book: {$res->getBook()->getTitle()}\n";
        echo "     Status: {$res->getStatus()}\n";
        echo "     Reserved at: {$res->getReservedAt()->format('Y-m-d H:i:s')}\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 4: List Book Reviews (book ID = 1)
echo "\n4. Testing ReviewRepository->findByBook(bookId=1)...\n";
try {
    $qb = $reviewRepo->createQueryBuilder('r')
        ->leftJoin('r.book', 'b')->addSelect('b')
        ->leftJoin('r.user', 'u')->addSelect('u')
        ->where('r.book = :bookId')
        ->setParameter('bookId', 1)
        ->setMaxResults(5);

    $reviews = $qb->getQuery()->getResult();

    echo "   OK - query executed successfully\n";
    echo "   Found: " . count($reviews) . " reviews\n";

    if (count($reviews) > 0) {
        $rev = $reviews[0];
        echo "   Sample review:\n";
        echo "     Rating: {$rev->getRating()}/5\n";
        echo "     Comment: " . substr($rev->getComment() ?? '', 0, 50) . "...\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== END OF TEST ===\n";
