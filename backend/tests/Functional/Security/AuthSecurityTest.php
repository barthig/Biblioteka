<?php

namespace App\Tests\Functional\Security;

use App\Entity\RefreshToken;
use App\Service\Auth\RefreshTokenService;
use App\Tests\Functional\ApiTestCase;

class AuthSecurityTest extends ApiTestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('API_SECRET=test-secret');
        $_ENV['API_SECRET'] = 'test-secret';
        $this->client = $this->createClientWithoutSecret();

        $cachePool = static::getContainer()->has('cache.rate_limiter')
            ? static::getContainer()->get('cache.rate_limiter')
            : null;
        if ($cachePool) {
            $cachePool->clear();
        }
    }

    public function testLoginRateLimitingIsEnforced(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->client->request('POST', '/api/auth/login', [], [], [
                'CONTENT_TYPE' => 'application/json',
                'REMOTE_ADDR' => '10.0.0.10',
            ], json_encode([
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]));

            if ($i < 5) {
                $this->assertNotEquals(429, $this->client->getResponse()->getStatusCode(),
                    "Request $i should not be rate limited yet");
            } else {
                $this->assertEquals(429, $this->client->getResponse()->getStatusCode(),
                    'Login should be rate limited after 5 attempts');

                $data = json_decode($this->client->getResponse()->getContent(), true);
                $message = $data['error']['message'] ?? $data['message'] ?? '';
                $this->assertStringContainsString('Zbyt wiele', $message);
            }
        }
    }

    public function testLoginFailsWhenRefreshTokenCreationFails(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();
        $mock = $this->createMock(RefreshTokenService::class);
        $mock->method('createRefreshToken')
            ->willThrowException(new \RuntimeException('Refresh token failure'));
        $container->set(RefreshTokenService::class, $mock);

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => '10.0.0.11',
        ], json_encode([
            'email' => 'verified@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(500, $client->getResponse()->getStatusCode(),
            'Login should return 500 when refresh token creation fails');

        $data = json_decode($client->getResponse()->getContent(), true);
        $message = $data['error']['message'] ?? $data['message'] ?? '';
        $this->assertStringContainsString('Failed to create session', $message);
        static::ensureKernelShutdown();
    }

    public function testApiSecretHeaderGrantsAccessToStaffRoute(): void
    {
        $this->client->request('GET', '/api/users', [], [], [
            'HTTP_X_API_SECRET' => 'test-secret',
        ]);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(),
            'API_SECRET should grant access to staff-only routes');
    }

    public function testInvalidApiSecretHeaderIsRejected(): void
    {
        $this->client->request('GET', '/api/users', [], [], [
            'HTTP_X_API_SECRET' => 'invalid-secret',
        ]);

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode(),
            'Invalid API_SECRET should be rejected');
    }

    public function testTestLoginEndpointIsDisabledInProduction(): void
    {
        $previousEnv = getenv('APP_ENV');
        $previousEnvVar = $_ENV['APP_ENV'] ?? null;
        putenv('APP_ENV=prod');
        $_ENV['APP_ENV'] = 'prod';

        try {
            $this->client->request('POST', '/api/test-login', [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_API_SECRET' => 'test-secret',
            ], json_encode([
                'email' => 'verified@example.com',
                'password' => 'password123',
            ]));

            $this->assertEquals(404, $this->client->getResponse()->getStatusCode(),
                'test-login should be disabled outside dev/test');
        } finally {
            if ($previousEnv === false) {
                putenv('APP_ENV');
                unset($_ENV['APP_ENV']);
            } else {
                putenv('APP_ENV=' . $previousEnv);
                $_ENV['APP_ENV'] = $previousEnvVar ?? $previousEnv;
            }
        }
    }

    public function testApiSecretInAuthorizationHeaderIsRejected(): void
    {
        $this->client->request('GET', '/api/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'change_me_api',
        ]);

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode(),
            'API_SECRET in Authorization header should not grant access');
    }

    public function testRefreshTokenRotationOnRefresh(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => '10.0.0.12',
        ], json_encode([
            'email' => 'verified@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $loginData = json_decode($this->client->getResponse()->getContent(), true);
        $firstRefreshToken = $loginData['refreshToken'];

        $this->client->request('POST', '/api/auth/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'refreshToken' => $firstRefreshToken
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $refreshData = json_decode($this->client->getResponse()->getContent(), true);
        $secondRefreshToken = $refreshData['refreshToken'] ?? null;

        $this->assertNotNull($secondRefreshToken, 'New refresh token should be returned');
        $this->assertNotEquals($firstRefreshToken, $secondRefreshToken,
            'Refresh token should be rotated (different from original)');

        $this->client->request('POST', '/api/auth/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'refreshToken' => $firstRefreshToken
        ]));

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode(),
            'Old refresh token should be invalid after rotation');
    }

    public function testRefreshTokenReuseDetection(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => '10.0.0.13',
        ], json_encode([
            'email' => 'verified@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $loginData = json_decode($this->client->getResponse()->getContent(), true);
        $refreshToken = $loginData['refreshToken'];

        $this->client->request('POST', '/api/auth/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refreshToken' => $refreshToken]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->client->request('POST', '/api/auth/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refreshToken' => $refreshToken]));

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode(),
            'Refresh token should not be reusable after being rotated');
    }

    public function testLoginReturns500OnRefreshTokenFailure(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();
        $mock = $this->createMock(RefreshTokenService::class);
        $mock->method('createRefreshToken')
            ->willThrowException(new \RuntimeException('Refresh token failure'));
        $container->set(RefreshTokenService::class, $mock);

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => '10.0.0.14',
        ], json_encode([
            'email' => 'verified@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(500, $client->getResponse()->getStatusCode(),
            'Login should return 500 when refresh token creation fails');

        $data = json_decode($client->getResponse()->getContent(), true);
        $message = $data['error']['message'] ?? $data['message'] ?? '';
        $this->assertStringContainsString('Failed to create session', $message);
        static::ensureKernelShutdown();
    }

    public function testRefreshTokensAreHashedInDatabase(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => '10.0.0.15',
        ], json_encode([
            'email' => 'verified@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $refreshTokenString = $data['refreshToken'] ?? null;
        $this->assertNotEmpty($refreshTokenString, 'Refresh token should be returned');

        $em = static::getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository(RefreshToken::class);
        $tokenHash = hash('sha256', $refreshTokenString);
        $refreshToken = $repo->findOneBy(['tokenHash' => $tokenHash]);

        $this->assertNotNull($refreshToken, 'Refresh token hash should be stored');
        $this->assertSame($tokenHash, $refreshToken->getTokenHash());
        $this->assertTrue($refreshToken->verifyToken($refreshTokenString));
    }
}
