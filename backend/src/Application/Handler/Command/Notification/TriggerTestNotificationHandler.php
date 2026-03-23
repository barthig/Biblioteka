<?php
declare(strict_types=1);

namespace App\Application\Handler\Command\Notification;

use App\Application\Command\Notification\TriggerTestNotificationCommand;
use App\Message\TestNotificationDispatchMessage;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final class TriggerTestNotificationHandler
{
    public function __construct(
        private readonly MessageBusInterface $bus
    ) {
    }

    public function __invoke(TriggerTestNotificationCommand $command): array
    {
        if (!$command->queueAvailable) {
            throw new ServiceUnavailableHttpException(null, 'Queue unavailable');
        }

        $this->bus->dispatch(new TestNotificationDispatchMessage(
            $command->channel,
            $command->target,
            $command->message,
            $command->requestedByUserId
        ));

        return [
            'status' => 'queued',
            'channel' => $command->channel,
            'target' => $command->target,
            'message' => $command->message,
        ];
    }
}
