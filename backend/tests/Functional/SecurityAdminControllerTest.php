<?php
namespace App\Tests\Functional;

class SecurityAdminControllerTest extends ApiTestCase
{
    public function testEndpointsRequireAdmin(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/admin/backups');
        $this->assertResponseStatusCodeSame(403);

        $this->sendRequest($client, 'POST', '/api/admin/backups');
        $this->assertResponseStatusCodeSame(403);

        $this->sendRequest($client, 'GET', '/api/admin/logs');
        $this->assertResponseStatusCodeSame(403);
    }
}
