<?php
namespace App\Controller;

use App\Application\Query\Dashboard\GetOverviewQuery;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly SecurityService $security,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function overview(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $isLibrarian = in_array('ROLE_LIBRARIAN', $user->getRoles());
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        // Base stats
        $envelope = $this->queryBus->dispatch(new GetOverviewQuery());
        $baseStats = $envelope->last(HandledStamp::class)?->getResult();

        $stats = $baseStats ?? [];

        // Get repositories
        $loanRepo = $this->entityManager->getRepository(\App\Entity\Loan::class);
        $reservationRepo = $this->entityManager->getRepository(\App\Entity\Reservation::class);
        $favoriteRepo = $this->entityManager->getRepository(\App\Entity\Favorite::class);

        // User-specific stats
        if (!$isLibrarian && !$isAdmin) {
            $stats['activeLoans'] = $loanRepo->count(['user' => $userId, 'returnedAt' => null]);
            $stats['activeReservations'] = $reservationRepo->count(['user' => $userId, 'status' => 'ACTIVE']);
            $stats['favoritesCount'] = $favoriteRepo->count(['user' => $userId]);
        }

        // Librarian stats
        if ($isLibrarian) {
            // Reservations ready to pick up (ACTIVE with assigned copy)
            $stats['pendingReservations'] = $reservationRepo->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.status = :status')
                ->andWhere('r.bookCopy IS NOT NULL')
                ->setParameter('status', 'ACTIVE')
                ->getQuery()
                ->getSingleScalarResult();

            // Overdue loans
            $stats['overdueLoans'] = $loanRepo->createQueryBuilder('l')
                ->select('COUNT(l.id)')
                ->where('l.returnedAt IS NULL')
                ->andWhere('l.dueAt < :now')
                ->setParameter('now', new \DateTimeImmutable())
                ->getQuery()
                ->getSingleScalarResult();

            // Expired reservations (not picked up)
            $stats['expiredReservations'] = $reservationRepo->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.status = :status')
                ->andWhere('r.bookCopy IS NOT NULL')
                ->andWhere('r.expiresAt < :now')
                ->setParameter('status', 'ACTIVE')
                ->setParameter('now', new \DateTimeImmutable())
                ->getQuery()
                ->getSingleScalarResult();
        }

        // Admin stats
        if ($isAdmin) {
            // Simulate active users (in real app, track sessions)
            $stats['activeUsers'] = rand(5, 20);
            $stats['serverLoad'] = rand(15, 45);
            $stats['transactionsToday'] = $loanRepo->createQueryBuilder('l')
                ->select('COUNT(l.id)')
                ->where('l.borrowedAt >= :today')
                ->setParameter('today', (new \DateTimeImmutable())->setTime(0, 0))
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->json($stats, 200);
    }
}
