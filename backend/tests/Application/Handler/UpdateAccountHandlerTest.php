<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Account\UpdateAccountCommand;
use App\Application\Handler\Command\UpdateAccountHandler;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateAccountHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UpdateAccountHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new UpdateAccountHandler($this->entityManager, $this->userRepository);
    }

    public function testUpdateAccountSuccess(): void
    {
        $user = $this->createMock(User::class);
        
        $this->userRepository->method('find')->with(1)->willReturn($user);
        $user->expects($this->once())->method('setEmail')->with('new@example.com');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UpdateAccountCommand(userId: 1, email: 'new@example.com');
        $result = ($this->handler)($command);

        $this->assertSame($user, $result);
    }
}
