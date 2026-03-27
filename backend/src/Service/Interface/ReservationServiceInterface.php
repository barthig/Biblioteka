<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Entity\Book;
use App\Entity\Reservation;
use App\Entity\User;

interface ReservationServiceInterface
{
    public function createReservation(User $user, Book $book): Reservation;

    public function cancelReservation(Reservation $reservation): void;

    public function fulfillReservation(Reservation $reservation): void;

    public function getUserActiveReservations(User $user): array;

    public function getBookReservations(Book $book): array;

    public function getNextInQueue(Book $book): ?Reservation;

    public function getQueuePosition(Reservation $reservation): int;

    public function expireOldReservations(): int;

    public function userHasReservation(User $user, Book $book): bool;
}
