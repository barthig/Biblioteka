<?php
namespace App\Application\Command\Reservation;

class CreateReservationCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly int $bookId,
        public readonly int $expiresInDays = 2
    ) {
    }
}
