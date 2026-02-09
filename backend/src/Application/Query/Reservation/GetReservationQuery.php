<?php
declare(strict_types=1);
namespace App\Application\Query\Reservation;

class GetReservationQuery
{
    public function __construct(
        public readonly int $reservationId
    ) {
    }
}
