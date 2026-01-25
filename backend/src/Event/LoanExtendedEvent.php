<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Loan;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a loan is extended
 */
class LoanExtendedEvent extends Event
{
    public const NAME = 'loan.extended';

    public function __construct(
        private readonly Loan $loan,
        private readonly \DateTimeImmutable $previousDueDate,
        private readonly \DateTimeImmutable $newDueDate,
        private readonly int $extensionNumber = 1
    ) {
    }

    public function getLoan(): Loan
    {
        return $this->loan;
    }

    public function getPreviousDueDate(): \DateTimeImmutable
    {
        return $this->previousDueDate;
    }

    public function getNewDueDate(): \DateTimeImmutable
    {
        return $this->newDueDate;
    }

    public function getExtensionNumber(): int
    {
        return $this->extensionNumber;
    }

    public function getUser(): \App\Entity\User
    {
        return $this->loan->getUser();
    }

    public function getBookCopy(): \App\Entity\BookCopy
    {
        return $this->loan->getBookCopy();
    }
}
