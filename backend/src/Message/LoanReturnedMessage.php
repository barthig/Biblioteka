<?php
declare(strict_types=1);

namespace App\Message;

final class LoanReturnedMessage implements NotificationMessageInterface
{
    public function __construct(
        private readonly int $loanId,
        private readonly int $userId,
        private readonly string $returnedAtIso,
        private readonly bool $overdue
    ) {
    }

    public function getLoanId(): int
    {
        return $this->loanId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getReturnedAtIso(): string
    {
        return $this->returnedAtIso;
    }

    public function isOverdue(): bool
    {
        return $this->overdue;
    }

    public function getType(): string
    {
        return NotificationMessageInterface::TYPE_LOAN_RETURNED;
    }

    public function getFingerprint(): string
    {
        return sprintf(
            'loan_returned_%d_%s_%d',
            $this->loanId,
            $this->returnedAtIso,
            $this->overdue ? 1 : 0
        );
    }

    public function getPayload(): array
    {
        return [
            'loanId' => $this->loanId,
            'returnedAt' => $this->returnedAtIso,
            'overdue' => $this->overdue,
        ];
    }
}
