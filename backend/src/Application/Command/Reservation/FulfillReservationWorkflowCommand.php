<?php
declare(strict_types=1);
namespace App\Application\Command\Reservation;

class FulfillReservationWorkflowCommand
{
    public function __construct(
        public readonly int $reservationId,
        public readonly int $actingUserId
    ) {
    }
}
