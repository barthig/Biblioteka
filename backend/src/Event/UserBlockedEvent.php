<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a user account is blocked
 */
class UserBlockedEvent extends Event
{
    public const NAME = 'user.blocked';

    public function __construct(
        private readonly User $user,
        private readonly ?string $reason = null,
        private readonly ?User $blockedBy = null,
        private readonly \DateTimeImmutable $blockedAt = new \DateTimeImmutable()
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getBlockedBy(): ?User
    {
        return $this->blockedBy;
    }

    public function getBlockedAt(): \DateTimeImmutable
    {
        return $this->blockedAt;
    }
}
