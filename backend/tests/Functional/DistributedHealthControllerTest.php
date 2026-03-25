<?php

namespace App\Tests\Functional;

class DistributedHealthControllerTest extends ApiTestCase
{
    public function testDistributedHealthReturnsAggregatedChecks(): void
    {
        $client = $this->createClientWithoutSecret();

        $client->request('GET', '/health/distributed');

        $this->assertContains($client->getResponse()->getStatusCode(), [200, 503]);
        $payload = $this->getJsonResponse($client);

        $this->assertContains($payload['status'] ?? null, ['ok', 'degraded']);
        $this->assertSame('standalone', $payload['mode'] ?? null);
        $this->assertSame('ok', $payload['checks']['database'] ?? null);
        $this->assertArrayHasKey('redis', $payload['checks'] ?? []);
        $this->assertArrayHasKey('rabbitmq', $payload['checks'] ?? []);
        $this->assertArrayHasKey('notification_service', $payload['checks'] ?? []);
        $this->assertArrayHasKey('recommendation_service', $payload['checks'] ?? []);
        $this->assertContains($payload['checks']['rabbitmq'] ?? null, ['ok', 'error']);
        $this->assertSame('skipped', $payload['checks']['notification_service'] ?? null);
        $this->assertSame('skipped', $payload['checks']['recommendation_service'] ?? null);
    }
}
