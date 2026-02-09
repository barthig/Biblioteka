<?php
declare(strict_types=1);
namespace App\Application\Command\Reservation;

class CancelReservationCommand
{
    public function __construct(
        public readonly int $reservationId,
        public readonly int $userId,
        public readonly bool $isLibrarian = false
    ) {
    }
}
