<?php
declare(strict_types=1);

namespace App\Application\Handler\Query\Notification;

use App\Application\Query\Notification\ListNotificationsQuery;
use App\Repository\NotificationLogRepository;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListNotificationsHandler
{
    public function __construct(
        private readonly NotificationLogRepository $notificationLogs
    ) {
    }

    public function __invoke(ListNotificationsQuery $query): array
    {
        if ($query->serviceDown) {
            throw new ServiceUnavailableHttpException(null, 'Notification service unavailable');
        }

        $logs = $this->notificationLogs->findInAppForUser($query->userId, $query->limit);
        $notifications = [];

        foreach ($logs as $log) {
            $payload = $log->getPayload() ?? [];
            $notifications[] = [
                'id' => $log->getId(),
                'type' => $payload['type'] ?? $log->getType(),
                'title' => $payload['title'] ?? null,
                'message' => $payload['message'] ?? null,
                'link' => $payload['link'] ?? null,
                'createdAt' => $log->getSentAt()->format(DATE_ATOM),
            ];
        }

        return $notifications;
    }
}
