<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\User\UpdateUserCommand;
use App\Application\Handler\Command\UpdateUserHandler;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateUserHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private UpdateUserHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new UpdateUserHandler($this->em, $this->userRepository);
    }

    public function testUpdateUserSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('setName')->with('Updated Name');
        $user->expects($this->once())->method('setEmail')->with('updated@example.com');

        $this->userRepository->method('find')->with(1)->willReturn($user);

        $this->em->expects($this->once())->method('persist')->with($user);
        $this->em->expects($this->once())->method('flush');

        $command = new UpdateUserCommand(
            userId: 1,
            name: 'Updated Name',
            email: 'updated@example.com'
        );

        $result = ($this->handler)($command);

        $this->assertSame($user, $result);
    }

    public function testUpdateUserContactInfo(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('setPhoneNumber')->with('+48987654321');
        $user->expects($this->once())->method('setCity')->with('Krakow');

        $this->userRepository->method('find')->with(1)->willReturn($user);

        $this->em->expects($this->once())->method('persist')->with($user);
        $this->em->expects($this->once())->method('flush');

        $command = new UpdateUserCommand(
            userId: 1,
            phoneNumber: '+48987654321',
            city: 'Krakow'
        );

        $result = ($this->handler)($command);

        $this->assertSame($user, $result);
    }

    public function testThrowsExceptionWhenUserNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found');

        $this->userRepository->method('find')->with(999)->willReturn(null);

        $command = new UpdateUserCommand(userId: 999, name: 'Test');
        ($this->handler)($command);
    }
}
