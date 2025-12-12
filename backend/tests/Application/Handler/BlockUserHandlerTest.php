<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\User\BlockUserCommand;
use App\Application\Handler\Command\BlockUserHandler;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlockUserHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private BlockUserHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new BlockUserHandler($this->em, $this->userRepository);
    }

    public function testBlockUserSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('block')->with('Policy violation');

        $this->userRepository->method('find')->with(1)->willReturn($user);

        $this->em->expects($this->once())->method('persist')->with($user);
        $this->em->expects($this->once())->method('flush');

        $command = new BlockUserCommand(userId: 1, reason: 'Policy violation');
        $result = ($this->handler)($command);

        $this->assertSame($user, $result);
    }

    public function testThrowsExceptionWhenUserNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('User not found');

        $this->userRepository->method('find')->with(999)->willReturn(null);

        $command = new BlockUserCommand(userId: 999, reason: 'Test');
        ($this->handler)($command);
    }
}
