<?php
namespace App\Tests\Functional;

use App\Entity\User;

class ApiAuthSubscriberTest extends ApiTestCase
{
    public function testInvalidJwtReturnsUnauthorized(): void
    {
        $client = $this->createClientWithoutSecret([
            'HTTP_AUTHORIZATION' => 'Bearer invalid-token',
        ]);

        $this->sendRequest($client, 'GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testBlockedUserReturnsForbidden(): void
    {
        $user = $this->createUser('blocked@example.com');
        $user->block('test');
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'GET', '/api/me');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPendingApprovalReturnsForbidden(): void
    {
        $user = $this->createUser('pending@example.com');
        $user->setPendingApproval(true);
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'GET', '/api/me');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testApiSecretAllowsAdminSettings(): void
    {
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/admin/system/settings');

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertArrayHasKey('settings', $payload);
    }
}
