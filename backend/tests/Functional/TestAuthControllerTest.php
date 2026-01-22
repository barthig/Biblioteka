<?php
namespace App\Tests\Functional;

class TestAuthControllerTest extends ApiTestCase
{
    public function testTestLoginRequiresEmailAndPassword(): void
    {
        putenv('APP_ENV=test');
        $_ENV['APP_ENV'] = 'test';

        $client = $this->createApiClient();
        $this->jsonRequest($client, 'POST', '/api/test-login', []);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testTestLoginRejectsInvalidPassword(): void
    {
        putenv('APP_ENV=test');
        $_ENV['APP_ENV'] = 'test';

        $user = $this->createUser('tester@example.com', ['ROLE_USER'], 'StrongPass1', 'Tester');
        $client = $this->createApiClient();

        $this->jsonRequest($client, 'POST', '/api/test-login', [
            'email' => $user->getEmail(),
            'password' => 'WrongPass1',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testTestLoginReturnsTokens(): void
    {
        putenv('APP_ENV=test');
        $_ENV['APP_ENV'] = 'test';

        $user = $this->createUser('tester@example.com', ['ROLE_USER'], 'StrongPass1', 'Tester');
        $client = $this->createApiClient();

        $this->jsonRequest($client, 'POST', '/api/test-login', [
            'email' => $user->getEmail(),
            'password' => 'StrongPass1',
        ]);

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        self::assertTrue($payload['success'] ?? false);
        self::assertArrayHasKey('token', $payload);
        self::assertArrayHasKey('refreshToken', $payload);
    }
}
