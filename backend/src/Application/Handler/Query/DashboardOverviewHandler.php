<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Dashboard\DashboardOverviewQuery;
use App\Entity\Book;
use App\Entity\Favorite;
use App\Entity\Loan;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class DashboardOverviewHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(DashboardOverviewQuery $query): array
    {
        $user = $this->entityManager->getRepository(User::class)->find($query->userId);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $isLibrarian = in_array('ROLE_LIBRARIAN', $user->getRoles());
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        $bookRepo = $this->entityManager->getRepository(Book::class);
        $userRepo = $this->entityManager->getRepository(User::class);
        $loanRepo = $this->entityManager->getRepository(Loan::class);
        $reservationRepo = $this->entityManager->getRepository(Reservation::class);
        $favoriteRepo = $this->entityManager->getRepository(Favorite::class);

        $userCount = 0;
        foreach ($userRepo->findAll() as $account) {
            if (!in_array('ROLE_SYSTEM', $account->getRoles(), true)) {
                $userCount++;
            }
        }

        $stats = [
            'booksCount' => $bookRepo->count([]),
            'usersCount' => $userCount,
            'loansCount' => $loanRepo->count(['returnedAt' => null]),
            'reservationsQueue' => $reservationRepo->count(['status' => Reservation::STATUS_ACTIVE]),
        ];

        if (!$isLibrarian && !$isAdmin) {
            $stats['activeLoans'] = $loanRepo->count(['user' => $query->userId, 'returnedAt' => null]);
            $stats['activeReservations'] = $reservationRepo->count(['user' => $query->userId, 'status' => 'ACTIVE']);
            $stats['favoritesCount'] = $favoriteRepo->count(['user' => $query->userId]);
        }

        if ($isLibrarian) {
            $stats['pendingReservations'] = $reservationRepo->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.status = :status')
                ->setParameter('status', 'ACTIVE')
                ->getQuery()
                ->getSingleScalarResult();

            $stats['overdueLoans'] = $loanRepo->createQueryBuilder('l')
                ->select('COUNT(l.id)')
                ->where('l.returnedAt IS NULL')
                ->andWhere('l.dueAt < :now')
                ->setParameter('now', new \DateTimeImmutable())
                ->getQuery()
                ->getSingleScalarResult();

            $stats['preparedReservations'] = $reservationRepo->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.status = :status')
                ->setParameter('status', 'PREPARED')
                ->getQuery()
                ->getSingleScalarResult();
        }

        if ($isAdmin) {
            $stats['activeUsers'] = rand(5, 20);
            $stats['serverLoad'] = rand(15, 45);
            $stats['transactionsToday'] = $loanRepo->createQueryBuilder('l')
                ->select('COUNT(l.id)')
                ->where('l.borrowedAt >= :today')
                ->setParameter('today', (new \DateTimeImmutable())->setTime(0, 0))
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $stats;
    }
}
