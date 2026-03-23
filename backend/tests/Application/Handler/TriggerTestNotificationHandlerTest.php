<?php

declare(strict_types=1);

namespace App\Tests\Application\Handler;

use App\Application\Command\Notification\TriggerTestNotificationCommand;
use App\Application\Handler\Command\Notification\TriggerTestNotificationHandler;
use App\Message\TestNotificationDispatchMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class TriggerTestNotificationHandlerTest extends TestCase
{
    public function testDispatchesAsyncTestNotificationMessage(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static fn (object $message): bool => $message instanceof TestNotificationDispatchMessage
                    && $message->getChannel() === 'email'
                    && $message->getTarget() === 'reader@example.com'
            ))
            ->willReturn(new Envelope(new \stdClass()));

        $handler = new TriggerTestNotificationHandler($bus);
        $result = $handler(new TriggerTestNotificationCommand(5, 'email', 'reader@example.com', 'Test message', true));

        self::assertSame('queued', $result['status']);
        self::assertSame('email', $result['channel']);
    }

    public function testThrowsWhenQueueUnavailable(): void
    {
        $handler = new TriggerTestNotificationHandler($this->createMock(MessageBusInterface::class));

        $this->expectException(ServiceUnavailableHttpException::class);
        $handler(new TriggerTestNotificationCommand(5, 'email', 'reader@example.com', 'Test message', false));
    }
}
