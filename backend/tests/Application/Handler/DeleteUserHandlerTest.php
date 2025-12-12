<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\User\DeleteUserCommand;
use App\Application\Handler\Command\DeleteUserHandler;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteUserHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private DeleteUserHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new DeleteUserHandler($this->entityManager, $this->userRepository);
    }

    public function testDeleteUserSuccess(): void
    {
        $user = $this->createMock(User::class);
        $this->userRepository->method('find')->with(1)->willReturn($user);
        $this->entityManager->expects($this->once())->method('remove')->with($user);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new DeleteUserCommand(userId: 1);
        ($this->handler)($command);

        $this->assertTrue(true);
    }

    public function testThrowsExceptionWhenUserNotFound(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->userRepository->method('find')->with(999)->willReturn(null);

        $command = new DeleteUserCommand(userId: 999);
        ($this->handler)($command);
    }
}
