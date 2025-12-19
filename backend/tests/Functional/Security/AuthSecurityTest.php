<?php

namespace App\Tests\Functional\Security;

use App\Entity\RefreshToken;
use App\Service\RefreshTokenService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security tests for authentication endpoints
 */
class AuthSecurityTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test that login rate limiting is enforced
     */
    public function testLoginRateLimitingIsEnforced(): void
    {
        // Attempt 6 logins from same IP (limit is 5)
        for ($i = 0; $i < 6; $i++) {
            $this->client->request('POST', '/api/auth/login', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]));

            if ($i < 5) {
                // First 5 attempts should get 401 (unauthorized)
                $this->assertNotEquals(429, $this->client->getResponse()->getStatusCode(),
                    "Request $i should not be rate limited yet");
            } else {
                // 6th attempt should be rate limited
                $this->assertEquals(429, $this->client->getResponse()->getStatusCode(),
                    'Login should be rate limited after 5 attempts');
                
                $data = json_decode($this->client->getResponse()->getContent(), true);
                $this->assertStringContainsString('Zbyt wiele', $data['message'] ?? '');
            }
        }
    }

    /**
     * Test that login fails if refresh token creation fails
     */
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
        ], json_encode([
            'email' => 'verified@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(500, $client->getResponse()->getStatusCode(),
            'Login should return 500 when refresh token creation fails');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Failed to create session', $data['message'] ?? '');
        static::ensureKernelShutdown();
    }

    /**
     * Test that API_SECRET header is rejected for protected routes
     */
    public function testApiSecretHeaderIsRejected(): void
    {
        // Try to access a protected endpoint with API_SECRET
        $this->client->request('GET', '/api/profile', [], [], [
            'HTTP_X_API_SECRET' => 'change_me_api',
        ]);

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode(),
            'API_SECRET should not grant access to protected routes');
    }

    /**
     * Test that API_SECRET in Authorization header is rejected
     */
    public function testApiSecretInAuthorizationHeaderIsRejected(): void
    {
        $this->client->request('GET', '/api/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'change_me_api',
        ]);

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode(),
            'API_SECRET in Authorization header should not grant access');
    }

    /**
     * Test that refresh token rotation occurs on token refresh
     */
    public function testRefreshTokenRotationOnRefresh(): void
    {
        // 1. Login to get initial tokens
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'verified@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $loginData = json_decode($this->client->getResponse()->getContent(), true);
        $firstRefreshToken = $loginData['refreshToken'];

        // 2. Use refresh token to get new access token
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

        // 3. Try to reuse the old refresh token - should fail
        $this->client->request('POST', '/api/auth/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'refreshToken' => $firstRefreshToken
        ]));

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode(),
            'Old refresh token should be invalid after rotation');
    }

    /**
     * Test that refresh tokens cannot be replayed after use
     */
    public function testRefreshTokenReuseDetection(): void
    {
        // Login
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'verified@example.com',
            'password' => 'password123'
        ]));

        $loginData = json_decode($this->client->getResponse()->getContent(), true);
        $refreshToken = $loginData['refreshToken'];

        // Use refresh token once
        $this->client->request('POST', '/api/auth/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refreshToken' => $refreshToken]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Try to use same refresh token again - should fail
        $this->client->request('POST', '/api/auth/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refreshToken' => $refreshToken]));

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode(),
            'Refresh token should not be reusable after being rotated');
    }

    /**
     * Test that login returns error (not 200) when refresh token creation fails
     */
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
        ], json_encode([
            'email' => 'verified@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(500, $client->getResponse()->getStatusCode(),
            'Login should return 500 when refresh token creation fails');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Failed to create session', $data['message'] ?? '');
        static::ensureKernelShutdown();
    }

    /**
     * Test that refresh tokens are stored hashed in database
     */
    public function testRefreshTokensAreHashedInDatabase(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
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
