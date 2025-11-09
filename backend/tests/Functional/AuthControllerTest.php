<?php
namespace App\Tests\Functional;

class AuthControllerTest extends ApiTestCase
{
    public function testLoginReturnsToken(): void
    {
        $this->createUser('user1@example.com', ['ROLE_USER'], 'password1');
        $token = $this->loginAndGetToken();
        self::assertNotEmpty($token, 'JWT token should not be empty');
    }

    public function testLoginFailsWithInvalidPassword(): void
    {
        $this->createUser('user1@example.com', ['ROLE_USER'], 'password1');
        $client = $this->createApiClient();
        $this->jsonRequest($client, 'POST', '/api/auth/login', [
            'email' => 'user1@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }
}
