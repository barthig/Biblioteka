<?php
namespace App\Application\Command\Reservation;

class FulfillReservationCommand
{
    public function __construct(
        public readonly int $reservationId,
        public readonly int $loanId
    ) {
    }
}
