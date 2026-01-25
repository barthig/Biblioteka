<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Loan;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a loan becomes overdue
 */
class LoanOverdueEvent extends Event
{
    public const NAME = 'loan.overdue';

    public function __construct(
        private readonly Loan $loan,
        private readonly int $daysOverdue,
        private readonly \DateTimeImmutable $detectedAt = new \DateTimeImmutable()
    ) {
    }

    public function getLoan(): Loan
    {
        return $this->loan;
    }

    public function getDaysOverdue(): int
    {
        return $this->daysOverdue;
    }

    public function getDetectedAt(): \DateTimeImmutable
    {
        return $this->detectedAt;
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
