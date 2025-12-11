<?php

namespace App\Tests\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Service\RefreshTokenService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RefreshTokenServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private RefreshTokenRepository $repository;
    private RefreshTokenService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(RefreshTokenRepository::class);
        $this->service = new RefreshTokenService($this->em, $this->repository);
    }

    public function testCreateRefreshToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $request = new Request([], [], [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'TestBrowser/1.0'
        ]);

        $this->repository
            ->expects($this->once())
            ->method('countUserActiveTokens')
            ->with($user)
            ->willReturn(0);

        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(RefreshToken::class));

        $this->em
            ->expects($this->once())
            ->method('flush');

        $token = $this->service->createRefreshToken($user, $request);

        $this->assertInstanceOf(RefreshToken::class, $token);
        $this->assertSame($user, $token->getUser());
        $this->assertNotEmpty($token->getToken());
        $this->assertEquals('127.0.0.1', $token->getIpAddress());
    }

    public function testValidateRefreshTokenValid(): void
    {
        $user = new User();
        $tokenString = 'valid-token-123';

        $refreshToken = new RefreshToken();
        $refreshToken->setToken($tokenString);
        $refreshToken->setUser($user);
        $refreshToken->setExpiresAt(new \DateTimeImmutable('+30 days'));

        $this->repository
            ->expects($this->once())
            ->method('findValidToken')
            ->with($tokenString)
            ->willReturn($refreshToken);

        $result = $this->service->validateRefreshToken($tokenString);

        $this->assertSame($user, $result);
    }

    public function testValidateRefreshTokenExpired(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findValidToken')
            ->with('expired-token')
            ->willReturn(null);

        $result = $this->service->validateRefreshToken('expired-token');

        $this->assertNull($result);
    }

    public function testRevokeRefreshToken(): void
    {
        $tokenString = 'token-to-revoke';
        $refreshToken = new RefreshToken();
        $refreshToken->setToken($tokenString);
        $refreshToken->setExpiresAt(new \DateTimeImmutable('+30 days'));

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $tokenString])
            ->willReturn($refreshToken);

        $this->em
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->revokeRefreshToken($tokenString);

        $this->assertTrue($result);
        $this->assertTrue($refreshToken->isRevoked());
        $this->assertNotNull($refreshToken->getRevokedAt());
    }

    public function testCleanupExpiredTokens(): void
    {
        $deletedCount = 5;

        $this->repository
            ->expects($this->once())
            ->method('deleteExpiredTokens')
            ->willReturn($deletedCount);

        $result = $this->service->cleanupExpiredTokens();

        $this->assertEquals($deletedCount, $result);
    }
}
