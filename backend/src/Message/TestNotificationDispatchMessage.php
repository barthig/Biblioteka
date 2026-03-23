<?php
declare(strict_types=1);

namespace App\Message;

final class TestNotificationDispatchMessage
{
    public function __construct(
        private readonly string $channel,
        private readonly string $target,
        private readonly string $message,
        private readonly int $requestedByUserId
    ) {
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getRequestedByUserId(): int
    {
        return $this->requestedByUserId;
    }
}
