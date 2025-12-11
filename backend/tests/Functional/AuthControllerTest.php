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
}
