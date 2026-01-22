<?php

declare(strict_types=1);

namespace App\Application\Command\Reservation;

final readonly class PrepareReservationCommand
{
    public function __construct(
        public int $reservationId,
        public int $actingUserId,
    ) {
    }
}
