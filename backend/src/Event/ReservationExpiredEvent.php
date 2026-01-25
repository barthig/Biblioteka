<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Reservation;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a reservation expires
 */
class ReservationExpiredEvent extends Event
{
    public const NAME = 'reservation.expired';

    public function __construct(
        private readonly Reservation $reservation,
        private readonly \DateTimeImmutable $expiredAt = new \DateTimeImmutable()
    ) {
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }

    public function getExpiredAt(): \DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function getUser(): \App\Entity\User
    {
        return $this->reservation->getUser();
    }

    public function getBook(): \App\Entity\Book
    {
        return $this->reservation->getBook();
    }
}
