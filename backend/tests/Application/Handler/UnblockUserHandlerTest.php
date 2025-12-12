<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\User\UnblockUserCommand;
use App\Application\Handler\Command\UnblockUserHandler;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UnblockUserHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UnblockUserHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new UnblockUserHandler($this->entityManager, $this->userRepository);
    }

    public function testUnblockUserSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('unblock');
        
        $this->userRepository->method('find')->with(1)->willReturn($user);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UnblockUserCommand(userId: 1);
        $result = ($this->handler)($command);

        $this->assertSame($user, $result);
    }

    public function testThrowsExceptionWhenUserNotFound(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->userRepository->method('find')->with(999)->willReturn(null);

        $command = new UnblockUserCommand(userId: 999);
        ($this->handler)($command);
    }
}
