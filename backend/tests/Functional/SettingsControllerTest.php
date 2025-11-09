<?php
namespace App\Tests\Functional;

class SettingsControllerTest extends ApiTestCase
{
    public function testGetSettingsRequiresLibrarian(): void
    {
        $user = $this->createUser('user@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->sendRequest($client, 'GET', '/api/settings');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetSettingsReturnsDefaults(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'GET', '/api/settings');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertSame(5, $data['loanLimitPerUser']);
    }

    public function testGetSettingsIntegrationDown(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'GET', '/api/settings?integrationsDown=1');

        $this->assertResponseStatusCodeSame(503);
    }

    public function testUpdateSettingsRequiresLibrarian(): void
    {
        $user = $this->createUser('user@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->jsonRequest($client, 'PATCH', '/api/settings', [
            'loanLimitPerUser' => 10,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateSettingsValidatesRanges(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'PATCH', '/api/settings', [
            'loanLimitPerUser' => 100,
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateSettingsWithBackendDown(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'PATCH', '/api/settings', [
            'notificationsEnabled' => false,
        ], ['HTTP_X_CONFIG_SERVICE' => 'offline']);

        $this->assertResponseStatusCodeSame(503);
    }

    public function testUpdateSettingsSuccess(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'PATCH', '/api/settings', [
            'loanLimitPerUser' => 6,
            'loanDurationDays' => 20,
            'notificationsEnabled' => false,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertTrue($data['updated']);
        $this->assertFalse($data['settings']['notificationsEnabled']);
    }
}
