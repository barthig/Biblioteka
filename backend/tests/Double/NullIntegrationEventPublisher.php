<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Service\Integration\IntegrationEventPublisher;

final class NullIntegrationEventPublisher extends IntegrationEventPublisher
{
    public function __construct()
    {
    }

    public function publish(string $routingKey, array $payload): void
    {
    }
}
