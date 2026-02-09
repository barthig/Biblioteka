<?php
declare(strict_types=1);
namespace App\Application\Command\Reservation;

class FulfillReservationCommand
{
    public function __construct(
        public readonly int $reservationId,
        public readonly int $loanId
    ) {
    }
}
