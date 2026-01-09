<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Loan;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a book is returned.
 */
final class BookReturnedEvent extends Event
{
    public const NAME = 'book.returned';

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

    public function isOverdue(): bool
    {
        $dueAt = $this->loan->getDueAt();
        $returnedAt = $this->loan->getReturnedAt();
        
        if (!$dueAt || !$returnedAt) {
            return false;
        }
        
        return $returnedAt > $dueAt;
    }
}
