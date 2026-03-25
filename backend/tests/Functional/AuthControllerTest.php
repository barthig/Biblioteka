<?php
namespace App\Tests\Functional;

class AuthControllerTest extends ApiTestCase
{
    public function testLoginReturnsToken(): void
    {
        $this->createUser('user1@example.com', ['ROLE_USER'], 'StrongPass1');
        $token = $this->loginAndGetToken();
        self::assertNotEmpty($token, 'JWT token should not be empty');
    }

    public function testLoginFailsWithInvalidPassword(): void
    {
        $this->createUser('user1@example.com', ['ROLE_USER'], 'StrongPass1');
        $client = $this->createApiClient();
        $this->jsonRequest($client, 'POST', '/api/auth/login', [
            'email' => 'user1@example.com',
            'password' => 'WrongPass1',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProfileAliasAndCanonicalRouteReturnAuthenticatedUser(): void
    {
        $user = $this->createUser('profile@example.com', ['ROLE_USER'], 'StrongPass1', 'Jan Profile');
        $client = $this->createAuthenticatedClientWithoutApiSecret($user);

        $this->sendRequest($client, 'GET', '/api/auth/profile');
        $this->assertResponseStatusCodeSame(200);
        $canonical = $this->getJsonResponse($client);

        $this->sendRequest($client, 'GET', '/api/profile');
        $this->assertResponseStatusCodeSame(200);
        $alias = $this->getJsonResponse($client);

        $this->assertSame($canonical['id'] ?? null, $alias['id'] ?? null);
        $this->assertSame('profile@example.com', $canonical['email'] ?? null);
        $this->assertSame('profile@example.com', $alias['email'] ?? null);
    }
}
