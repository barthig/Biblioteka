<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Dashboard\GetOverviewQuery;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetOverviewHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(GetOverviewQuery $query): array
    {
        $bookCount = (int) $this->entityManager
            ->createQuery('SELECT COUNT(b.id) FROM App\\Entity\\Book b')
            ->getSingleScalarResult();

        $userCount = (int) $this->entityManager
            ->createQuery('SELECT COUNT(u.id) FROM App\\Entity\\User u')
            ->getSingleScalarResult();

        $activeLoans = (int) $this->entityManager
            ->createQuery('SELECT COUNT(l.id) FROM App\\Entity\\Loan l WHERE l.returnedAt IS NULL')
            ->getSingleScalarResult();

        $queueCount = (int) $this->entityManager
            ->createQuery('SELECT COUNT(r.id) FROM App\\Entity\\Reservation r WHERE r.status = :status')
            ->setParameter('status', Reservation::STATUS_ACTIVE)
            ->getSingleScalarResult();

        return [
            'booksCount' => $bookCount,
            'usersCount' => $userCount,
            'loansCount' => $activeLoans,
            'reservationsQueue' => $queueCount,
        ];
    }
}
