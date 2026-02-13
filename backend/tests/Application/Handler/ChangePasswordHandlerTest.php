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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangePasswordHandlerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepository&MockObject $userRepository;
    private RefreshTokenRepository&MockObject $refreshTokenRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private ChangePasswordHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->handler = new ChangePasswordHandler(
            $this->entityManager,
            $this->userRepository,
            $this->refreshTokenRepository,
            $this->passwordHasher
        );
    }

    public function testChangePasswordSuccess(): void
    {
        $user = $this->createMock(User::class);

        $this->passwordHasher->method('isPasswordValid')
            ->with($user, 'OldPassword1')
            ->willReturn(true);
        $this->passwordHasher->method('hashPassword')
            ->with($user, 'NewPassword1')
            ->willReturn('hashed_new_password');

        $user->expects($this->once())->method('setPassword')->with('hashed_new_password');

        $this->userRepository->method('find')->with(1)->willReturn($user);
        $this->refreshTokenRepository->expects($this->once())->method('revokeAllUserTokens')->with($user);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new ChangePasswordCommand(
            userId: 1,
            currentPassword: 'OldPassword1',
            newPassword: 'NewPassword1',
            confirmPassword: 'NewPassword1'
        );
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
