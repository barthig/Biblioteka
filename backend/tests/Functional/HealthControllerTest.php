<?php

namespace App\Tests\Functional;

class HealthControllerTest extends ApiTestCase
{
    public function testHealthEndpointReportsDatabaseStatus(): void
    {
        $client = $this->createClientWithoutSecret();

        $client->request('GET', '/health');

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($client);
        self::assertSame('ok', $data['status'] ?? null);
        self::assertSame('ok', $data['checks']['database'] ?? null);
    }
}
