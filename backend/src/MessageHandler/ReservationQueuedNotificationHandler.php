<?php
declare(strict_types=1);
namespace App\MessageHandler;

use App\Message\ReservationQueuedNotification;

class ReservationQueuedNotificationHandler
{
    public function __construct(private string $projectDir)
    {
    }

    public function __invoke(ReservationQueuedNotification $message): void
    {
        $logDir = $this->projectDir . '/var/log';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $entry = sprintf(
            "%s reservation:%d book:%d user:%s%s",
            (new \DateTimeImmutable())->format('c'),
            $message->getReservationId(),
            $message->getBookId(),
            $message->getUserEmail(),
            PHP_EOL
        );

        file_put_contents($logDir . '/reservation_queue.log', $entry, FILE_APPEND);
    }
}
