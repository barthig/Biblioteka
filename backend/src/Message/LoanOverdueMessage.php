<?php
namespace App\Message;

class LoanOverdueMessage implements NotificationMessageInterface
{
    public function __construct(
        private int $loanId,
        private int $userId,
        private string $dueAtIso,
        private int $daysLate
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

    public function getDaysLate(): int
    {
        return $this->daysLate;
    }

    public function getType(): string
    {
        return NotificationMessageInterface::TYPE_LOAN_OVERDUE;
    }

    public function getFingerprint(): string
    {
        return sprintf('loan_overdue_%d_%s', $this->loanId, $this->dueAtIso);
    }

    public function getPayload(): array
    {
        return [
            'loanId' => $this->loanId,
            'dueAt' => $this->dueAtIso,
            'daysLate' => $this->daysLate,
        ];
    }
}
