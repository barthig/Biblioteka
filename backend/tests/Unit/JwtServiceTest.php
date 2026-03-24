<?php

namespace App\Tests\Unit;

use App\Service\Auth\JwtService;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

class JwtServiceTest extends TestCase
{
    private const PRIMARY_SECRET = 'test_jwt_secret_for_backend_suite_123';
    private const LEGACY_SECRET = 'legacy_jwt_secret_for_backend_suite_456';

    protected function setUp(): void
    {
        putenv('JWT_SECRET=' . self::PRIMARY_SECRET);
        $_ENV['JWT_SECRET'] = self::PRIMARY_SECRET;
        putenv('JWT_SECRETS');
        unset($_ENV['JWT_SECRETS']);
    }

    public function testCreateAndValidateToken(): void
    {
        $token = JwtService::createToken(['sub' => 123, 'roles' => ['ROLE_USER']], 3600);
        $payload = JwtService::validateToken($token);

        $this->assertIsArray($payload);
        $this->assertEquals(123, $payload['sub']);
        $this->assertContains('ROLE_USER', $payload['roles']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function testExpiredTokenReturnsNull(): void
    {
        $token = JwtService::createToken(['sub' => 1], -40);
        $this->assertNull(JwtService::validateToken($token));
    }

    public function testTokenWithInvalidIssuerReturnsNull(): void
    {
        $now = time();
        $token = JWT::encode([
            'sub' => 1,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 300,
            'iss' => 'wrong-issuer',
            'aud' => 'biblioteka-api',
            'jti' => 'issuer-test',
        ], self::PRIMARY_SECRET, 'HS256', '1');

        $this->assertNull(JwtService::validateToken($token));
    }

    public function testTokenWithInvalidAudienceReturnsNull(): void
    {
        $now = time();
        $token = JWT::encode([
            'sub' => 1,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 300,
            'iss' => 'biblioteka',
            'aud' => 'wrong-audience',
            'jti' => 'audience-test',
        ], self::PRIMARY_SECRET, 'HS256', '1');

        $this->assertNull(JwtService::validateToken($token));
    }

    public function testTokenWithNonNumericSubjectReturnsNull(): void
    {
        $now = time();
        $token = JWT::encode([
            'sub' => 'service-account',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 300,
            'iss' => 'biblioteka',
            'aud' => 'biblioteka-api',
            'jti' => 'subject-test',
        ], self::PRIMARY_SECRET, 'HS256', '1');

        $this->assertNull(JwtService::validateToken($token));
    }

    public function testTokenCanBeValidatedWithRotatedSecretsEvenWhenKidIsWrong(): void
    {
        putenv('JWT_SECRETS=' . self::PRIMARY_SECRET . ',' . self::LEGACY_SECRET);
        $_ENV['JWT_SECRETS'] = self::PRIMARY_SECRET . ',' . self::LEGACY_SECRET;
        putenv('JWT_SECRET');
        unset($_ENV['JWT_SECRET']);

        $now = time();
        $token = JWT::encode([
            'sub' => 7,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 300,
            'iss' => 'biblioteka',
            'aud' => 'biblioteka-api',
            'jti' => 'rotation-test',
        ], self::LEGACY_SECRET, 'HS256', '99');

        $payload = JwtService::validateToken($token);

        $this->assertIsArray($payload);
        $this->assertSame(7, $payload['sub']);
    }
}