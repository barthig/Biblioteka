<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Fine;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a fine is created
 */
class FineCreatedEvent extends Event
{
    public const NAME = 'fine.created';

    public function __construct(
        private readonly Fine $fine,
        private readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable()
    ) {
    }

    public function getFine(): Fine
    {
        return $this->fine;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUser(): \App\Entity\User
    {
        return $this->fine->getUser();
    }

    public function getAmount(): float
    {
        return (float) $this->fine->getAmount();
    }

    public function getReason(): string
    {
        return $this->fine->getReason();
    }
}
