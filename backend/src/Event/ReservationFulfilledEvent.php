<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Reservation;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a reservation is fulfilled (book becomes available)
 */
class ReservationFulfilledEvent extends Event
{
    public const NAME = 'reservation.fulfilled';

    public function __construct(
        private readonly Reservation $reservation,
        private readonly \DateTimeImmutable $fulfilledAt = new \DateTimeImmutable()
    ) {
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }

    public function getFulfilledAt(): \DateTimeImmutable
    {
        return $this->fulfilledAt;
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
