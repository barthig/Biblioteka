<?php
declare(strict_types=1);
namespace App\Message;

class ReservationQueuedNotification
{
    public function __construct(
        private int $reservationId,
        private int $bookId,
        private string $userEmail
    ) {}

    public function getReservationId(): int
    {
        return $this->reservationId;
    }

    public function getBookId(): int
    {
        return $this->bookId;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }
}
