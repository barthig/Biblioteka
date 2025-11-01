<?php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Service\JwtService;

class JwtServiceTest extends TestCase
{
    protected function setUp(): void
    {
        // ensure secret available for tests
        $_ENV['JWT_SECRET'] = 'test_jwt_secret';
    }

    public function testCreateAndValidateToken()
    {
        $token = JwtService::createToken(['sub' => 123, 'roles' => ['ROLE_USER']], 3600);
        $payload = JwtService::validateToken($token);
        $this->assertIsArray($payload);
        $this->assertEquals(123, $payload['sub']);
        $this->assertContains('ROLE_USER', $payload['roles']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function testExpiredTokenReturnsNull()
    {
        $token = JwtService::createToken(['sub' => 1], -10);
        $this->assertNull(JwtService::validateToken($token));
    }
}
