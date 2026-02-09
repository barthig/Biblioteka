<?php
declare(strict_types=1);
namespace App\Application\Command\Reservation;

class ExpireReservationCommand
{
    public function __construct(
        public readonly int $reservationId
    ) {
    }
}
