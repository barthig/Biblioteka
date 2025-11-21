<?php
namespace App\Message;

class LoanDueReminderMessage implements NotificationMessageInterface
{
    public function __construct(
        private int $loanId,
        private int $userId,
        private string $dueAtIso
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
        return NotificationMessageInterface::TYPE_LOAN_DUE;
    }

    public function getFingerprint(): string
    {
        return sprintf('loan_due_%d_%s', $this->loanId, $this->dueAtIso);
    }

    public function getPayload(): array
    {
        return [
            'loanId' => $this->loanId,
            'dueAt' => $this->dueAtIso,
        ];
    }
}
