<?php
declare(strict_types=1);
namespace App\Message;

interface NotificationMessageInterface
{
    public const TYPE_LOAN_DUE = 'loan_due';
    public const TYPE_LOAN_OVERDUE = 'loan_overdue';
    public const TYPE_RESERVATION_READY = 'reservation_ready';

    public function getUserId(): int;

    public function getType(): string;

    public function getFingerprint(): string;

    /** @return array<string, mixed> */
    public function getPayload(): array;
}
