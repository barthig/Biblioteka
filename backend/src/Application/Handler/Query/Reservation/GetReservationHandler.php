<?php
declare(strict_types=1);
namespace App\Application\Handler\Query\Reservation;

use App\Application\Query\Reservation\GetReservationQuery;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetReservationHandler
{
    public function __construct(
        private ReservationRepository $reservationRepository
    ) {
    }

    public function __invoke(GetReservationQuery $query): ?Reservation
    {
        return $this->reservationRepository->find($query->reservationId);
    }
}
