<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Reservation;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a reservation is created
 */
class ReservationCreatedEvent extends Event
{
    public const NAME = 'reservation.created';

    public function __construct(
        private readonly Reservation $reservation,
        private readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable()
    ) {
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUser(): \App\Entity\User
    {
        return $this->reservation->getUser();
    }

    public function getBook(): \App\Entity\Book
    {
        return $this->reservation->getBook();
    }

    /**
     * Queue position is not tracked on Reservation entity.
     * Returns 0 as placeholder; implement queue counting logic if needed.
     */
    public function getQueuePosition(): int
    {
        return 0;
    }
}
