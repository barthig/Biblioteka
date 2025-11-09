<?php
namespace App\Service;

use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Repository\BookCopyRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookService
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function borrow(Book $book, ?Reservation $reservation = null): ?BookCopy
    {
        /** @var BookCopyRepository $repo */
        $repo = $this->doctrine->getRepository(BookCopy::class);
        $available = $repo->findAvailableCopies($book, 1);
        if (empty($available)) {
            return null;
        }

        $copy = $available[0];
        $copy->setStatus(BookCopy::STATUS_BORROWED);
        if ($reservation) {
            $reservation->assignBookCopy($copy)->markFulfilled();
        }

        $book->recalculateInventoryCounters();

        $em = $this->doctrine->getManager();
        $em->persist($copy);
        $em->persist($book);
        if ($reservation) {
            $em->persist($reservation);
        }
        $em->flush();

        return $copy;
    }

    public function restore(Book $book, ?BookCopy $copy = null, bool $recalculate = true): void
    {
        if ($copy) {
            $copy->setStatus(BookCopy::STATUS_AVAILABLE);
        }

        if ($recalculate) {
            $book->recalculateInventoryCounters();
        }

        $em = $this->doctrine->getManager();
        if ($copy) {
            $em->persist($copy);
        }
        $em->persist($book);
        $em->flush();
    }

    public function markCopyDamaged(BookCopy $copy, string $note = null): void
    {
        $copy->setStatus(BookCopy::STATUS_MAINTENANCE);
        if ($note !== null) {
            $copy->setConditionState($note);
        }

        $book = $copy->getBook();
        $book->recalculateInventoryCounters();

        $em = $this->doctrine->getManager();
        $em->persist($copy);
        $em->persist($book);
        $em->flush();
    }
}
