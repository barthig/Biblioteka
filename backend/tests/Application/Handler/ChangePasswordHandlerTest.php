<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Account\ChangePasswordCommand;
use App\Application\Handler\Command\ChangePasswordHandler;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangePasswordHandlerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepository&MockObject $userRepository;
    private RefreshTokenRepository&MockObject $refreshTokenRepository;
    private ChangePasswordHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepository::class);
        $this->handler = new ChangePasswordHandler(
            $this->entityManager,
            $this->userRepository,
            $this->refreshTokenRepository
        );
    }

    public function testChangePasswordSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getPassword')->willReturn(password_hash('oldpassword', PASSWORD_BCRYPT));
        $user->expects($this->once())->method('setPassword');
        
        $this->userRepository->method('find')->with(1)->willReturn($user);
        $this->refreshTokenRepository->expects($this->once())->method('revokeAllUserTokens')->with($user);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new ChangePasswordCommand(
            userId: 1,
            currentPassword: 'oldpassword',
            newPassword: 'newpassword',
            confirmPassword: 'newpassword'
        );
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
