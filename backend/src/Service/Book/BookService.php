<?php
namespace App\Service\Book;

use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Repository\BookCopyRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for managing book borrowing and returning operations.
 *
 * Handles the business logic for book copy management including
 * borrowing, returning, and status updates.
 */
class BookService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookCopyRepository $bookCopyRepository
    ) {
    }

    /**
     * Borrows a book copy for a user.
     *
     * Handles the borrowing process including:
     * - Finding available copy (or using preferred copy)
     * - Updating copy status to BORROWED
     * - Fulfilling reservation if applicable
     * - Recalculating inventory counters
     *
     * @param Book $book The book to borrow
     * @param Reservation|null $reservation Optional reservation to fulfill
     * @param BookCopy|null $preferredCopy Optional specific copy to borrow
     * @param bool $flush Whether to flush changes to database
     *
     * @return BookCopy|null The borrowed copy, or null if unavailable
     */
    public function borrow(Book $book, ?Reservation $reservation = null, ?BookCopy $preferredCopy = null, bool $flush = true): ?BookCopy
    {
        if ($preferredCopy !== null) {
            if ($preferredCopy->getBook()->getId() !== $book->getId()) {
                return null;
            }

            $status = $preferredCopy->getStatus();
            if (!in_array($status, [BookCopy::STATUS_AVAILABLE, BookCopy::STATUS_RESERVED], true)) {
                return null;
            }

            $copy = $preferredCopy;
        } else {
            $available = $this->bookCopyRepository->findAvailableCopies($book, 1);
            if (empty($available)) {
                return null;
            }
            $copy = $available[0];
        }
        $copy->setStatus(BookCopy::STATUS_BORROWED);
        if ($reservation) {
            $reservation->assignBookCopy($copy)->markFulfilled();
        }

        $book->recalculateInventoryCounters();

        $this->entityManager->persist($copy);
        $this->entityManager->persist($book);
        if ($reservation) {
            $this->entityManager->persist($reservation);
        }
        
        if ($flush) {
            $this->entityManager->flush();
        }

        return $copy;
    }

    /**
     * Reserves a book copy (marks as RESERVED).
     *
     * Finds an available copy matching preferred access types and marks it as reserved.
     *
     * @param Book $book The book to reserve
     * @param string[]|null $preferredAccessTypes Preferred access types (e.g., ['on-site', 'home'])
     * @param bool $allowFallback Whether to allow any access type if preferred not available
     *
     * @return BookCopy|null The reserved copy, or null if none available
     */
    public function reserveCopy(Book $book, ?array $preferredAccessTypes = null, bool $allowFallback = false): ?BookCopy
    {
        $available = $this->bookCopyRepository->findAvailableCopies($book, 1, $preferredAccessTypes);
        if (empty($available) && $allowFallback) {
            $available = $this->bookCopyRepository->findAvailableCopies($book, 1);
        }
        if (empty($available)) {
            return null;
        }

        $copy = $available[0];
        $copy->setStatus(BookCopy::STATUS_RESERVED);
        $book->recalculateInventoryCounters();

        $this->entityManager->persist($copy);
        $this->entityManager->persist($book);
        $this->entityManager->flush();

        return $copy;
    }

    /**
     * Releases a reserved copy back to available status.
     *
     * @param Book $book The book containing the copy
     * @param BookCopy $copy The reserved copy to release
     */
    public function releaseReservedCopy(Book $book, BookCopy $copy): void
    {
        $copy->setStatus(BookCopy::STATUS_AVAILABLE);
        $book->recalculateInventoryCounters();

        $this->entityManager->persist($copy);
        $this->entityManager->persist($book);
        $this->entityManager->flush();
    }

    /**
     * Restores a book copy to available status (after return).
     *
     * @param Book $book The book containing the copy
     * @param BookCopy|null $copy The copy to restore (if null, only recalculates)
     * @param bool $recalculate Whether to recalculate inventory counters
     * @param bool $flush Whether to flush changes to database
     */
    public function restore(Book $book, ?BookCopy $copy = null, bool $recalculate = true, bool $flush = true): void
    {
        if ($copy) {
            $copy->setStatus(BookCopy::STATUS_AVAILABLE);
            $this->entityManager->persist($copy);
        }

        if ($recalculate) {
            $book->recalculateInventoryCounters();
        }

        $this->entityManager->persist($book);
        
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function markCopyDamaged(BookCopy $copy, ?string $note = null): void
    {
        $copy->setStatus(BookCopy::STATUS_MAINTENANCE);
        if ($note !== null) {
            $copy->setConditionState($note);
        }

        $book = $copy->getBook();
        $book->recalculateInventoryCounters();

        $this->entityManager->persist($copy);
        $this->entityManager->persist($book);
        $this->entityManager->flush();
    }

    public function withdrawCopy(Book $book, BookCopy $copy, ?string $conditionNote = null, bool $flush = true): void
    {
        $copy->setStatus(BookCopy::STATUS_WITHDRAWN);
        if ($conditionNote !== null) {
            $copy->setConditionState($conditionNote);
        }

        $book->recalculateInventoryCounters();

        $this->entityManager->persist($copy);
        $this->entityManager->persist($book);
        if ($flush) {
            $this->entityManager->flush();
        }
    }
}

