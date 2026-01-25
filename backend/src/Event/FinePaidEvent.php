<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Fine;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a fine is paid
 */
class FinePaidEvent extends Event
{
    public const NAME = 'fine.paid';

    public function __construct(
        private readonly Fine $fine,
        private readonly string $paymentMethod = 'cash',
        private readonly \DateTimeImmutable $paidAt = new \DateTimeImmutable()
    ) {
    }

    public function getFine(): Fine
    {
        return $this->fine;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function getPaidAt(): \DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getUser(): \App\Entity\User
    {
        return $this->fine->getUser();
    }

    public function getAmount(): float
    {
        return (float) $this->fine->getAmount();
    }
}
