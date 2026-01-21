<?php
namespace App\Tests\Functional;

class AlertControllerTest extends ApiTestCase
{
    public function testAlertsRequireAuthentication(): void
    {
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/alerts');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLibraryHoursReturnsData(): void
    {
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/library-hours');

        $this->assertResponseStatusCodeSame(200);
    }
}
