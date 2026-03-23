<?php
declare(strict_types=1);

namespace App\Message;

final class LoanBorrowedMessage implements NotificationMessageInterface
{
    public function __construct(
        private readonly int $loanId,
        private readonly int $userId,
        private readonly string $dueAtIso
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

    public function getDueAtIso(): string
    {
        return $this->dueAtIso;
    }

    public function getType(): string
    {
        return NotificationMessageInterface::TYPE_LOAN_BORROWED;
    }

    public function getFingerprint(): string
    {
        return sprintf('loan_borrowed_%d_%s', $this->loanId, $this->dueAtIso);
    }

    public function getPayload(): array
    {
        return [
            'loanId' => $this->loanId,
            'dueAt' => $this->dueAtIso,
        ];
    }
}
