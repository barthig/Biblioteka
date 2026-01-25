<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Book;

/**
 * Interface for Reservation-related business operations
 */
interface ReservationServiceInterface
{
    /**
     * Create a new reservation
     * 
     * @throws \App\Exception\BookAlreadyAvailableException
     * @throws \App\Exception\UserAlreadyHasReservationException
     * @throws \App\Exception\UserBlockedException
     */
    public function createReservation(User $user, Book $book): Reservation;

    /**
     * Cancel a reservation
     */
    public function cancelReservation(Reservation $reservation): void;

    /**
     * Fulfill a reservation (when book becomes available)
     */
    public function fulfillReservation(Reservation $reservation): void;

    /**
     * Get user's active reservations
     */
    public function getUserActiveReservations(User $user): array;

    /**
     * Get reservations for a book
     */
    public function getBookReservations(Book $book): array;

    /**
     * Get next user in queue for a book
     */
    public function getNextInQueue(Book $book): ?Reservation;

    /**
     * Get user's position in reservation queue
     */
    public function getQueuePosition(Reservation $reservation): int;

    /**
     * Expire old reservations
     * 
     * @return int Number of expired reservations
     */
    public function expireOldReservations(): int;

    /**
     * Check if user has reservation for book
     */
    public function userHasReservation(User $user, Book $book): bool;
}
