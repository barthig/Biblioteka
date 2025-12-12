<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\User\CreateUserCommand;
use App\Application\Handler\Command\CreateUserHandler;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateUserHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private CreateUserHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->handler = new CreateUserHandler($this->em);
    }

    public function testCreateUserSuccess(): void
    {
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush');

        $command = new CreateUserCommand(
            email: 'test@example.com',
            name: 'Test User',
            password: 'password123',
            roles: ['ROLE_USER'],
            membershipGroup: User::GROUP_STANDARD,
            loanLimit: 5
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(User::class, $result);
    }

    public function testCreateUserWithContactInfo(): void
    {
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush');

        $command = new CreateUserCommand(
            email: 'test@example.com',
            name: 'Test User',
            password: 'password123',
            roles: ['ROLE_USER'],
            phoneNumber: '+48123456789',
            addressLine: 'Test Street 1',
            city: 'Warsaw',
            postalCode: '00-001'
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(User::class, $result);
    }

    public function testCreateBlockedUser(): void
    {
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush');

        $command = new CreateUserCommand(
            email: 'blocked@example.com',
            name: 'Blocked User',
            password: 'password123',
            roles: ['ROLE_USER'],
            blocked: true,
            blockedReason: 'Policy violation'
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(User::class, $result);
    }

    public function testThrowsExceptionOnInvalidMembershipGroup(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown membership group');

        $command = new CreateUserCommand(
            email: 'test@example.com',
            name: 'Test User',
            password: 'password123',
            roles: ['ROLE_USER'],
            membershipGroup: 'INVALID_GROUP'
        );

        ($this->handler)($command);
    }
}
