<?php
namespace App\Application\Command\Reservation;

class ExpireReservationCommand
{
    public function __construct(
        public readonly int $reservationId
    ) {
    }
}
