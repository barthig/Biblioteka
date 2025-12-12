<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Account\ChangePasswordCommand;
use App\Application\Handler\Command\ChangePasswordHandler;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangePasswordHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private ChangePasswordHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->handler = new ChangePasswordHandler($this->entityManager, $this->userRepository, $this->passwordHasher);
    }

    public function testChangePasswordSuccess(): void
    {
        $user = $this->createMock(User::class);
        
        $this->userRepository->method('find')->with(1)->willReturn($user);
        $this->passwordHasher->method('hashPassword')->willReturn('hashed_password');
        $user->expects($this->once())->method('setPassword')->with('hashed_password');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new ChangePasswordCommand(userId: 1, newPassword: 'new_password');
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
