<?php
declare(strict_types=1);

namespace App\Application\Command\Notification;

final class TriggerTestNotificationCommand
{
    public function __construct(
        public readonly int $requestedByUserId,
        public readonly string $channel,
        public readonly string $target,
        public readonly string $message,
        public readonly bool $queueAvailable = true
    ) {
    }
}
