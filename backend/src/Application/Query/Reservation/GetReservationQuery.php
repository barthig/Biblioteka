<?php
namespace App\Application\Query\Reservation;

class GetReservationQuery
{
    public function __construct(
        public readonly int $reservationId
    ) {
    }
}
