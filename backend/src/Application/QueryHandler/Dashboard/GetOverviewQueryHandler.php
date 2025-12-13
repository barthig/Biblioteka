<?php

namespace App\Application\QueryHandler\Dashboard;

use App\Application\Query\Dashboard\GetOverviewQuery;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetOverviewQueryHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function __invoke(GetOverviewQuery $query): array
    {
        $bookRepo = $this->em->getRepository(Book::class);
        $userRepo = $this->em->getRepository(User::class);
        $loanRepo = $this->em->getRepository(Loan::class);
        $reservationRepo = $this->em->getRepository(Reservation::class);

        return [
            'totalBooks' => $bookRepo->count([]),
            'totalUsers' => $userRepo->count([]),
            'activeLoans' => $loanRepo->count(['returnedAt' => null]),
            'activeReservations' => $reservationRepo->count(['status' => 'ACTIVE']),
        ];
    }
}
