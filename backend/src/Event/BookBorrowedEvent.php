<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Loan;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a book is borrowed.
 */
final class BookBorrowedEvent extends Event
{
    public const NAME = 'book.borrowed';

    public function __construct(
        private readonly Loan $loan
    ) {}

    public function getLoan(): Loan
    {
        return $this->loan;
    }

    public function getBookId(): ?int
    {
        return $this->loan->getBook()?->getId();
    }

    public function getUserId(): ?int
    {
        return $this->loan->getUser()?->getId();
    }
}
