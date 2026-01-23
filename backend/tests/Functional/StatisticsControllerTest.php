<?php
namespace App\Tests\Functional;

class StatisticsControllerTest extends ApiTestCase
{
    public function testDashboardRequiresLibrarian(): void
    {
        $user = $this->createUser('reader@example.com');
        $client = $this->createAuthenticatedClientWithoutApiSecret($user);
        $this->sendRequest($client, 'GET', '/api/statistics/dashboard');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDashboardReturnsStatsForLibrarian(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'GET', '/api/statistics/dashboard');

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertArrayHasKey('activeLoans', $payload);
        $this->assertArrayHasKey('totalUsers', $payload);
    }
}
