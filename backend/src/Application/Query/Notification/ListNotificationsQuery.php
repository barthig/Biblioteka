<?php
declare(strict_types=1);

namespace App\Application\Query\Notification;

final class ListNotificationsQuery
{
    public function __construct(
        public readonly int $userId,
        public readonly int $limit = 20,
        public readonly bool $serviceDown = false
    ) {
    }
}
