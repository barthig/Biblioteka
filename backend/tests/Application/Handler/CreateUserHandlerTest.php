<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\User\CreateUserCommand;
use App\Application\Handler\Command\CreateUserHandler;
use App\Entity\User;
use App\Exception\ValidationException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserHandlerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private Connection&MockObject $connection;
    private CreateUserHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->connection = $this->createMock(Connection::class);

        $this->em->method('getConnection')->willReturn($this->connection);
        $this->passwordHasher->method('hashPassword')->willReturn('hashed_password');

        $this->handler = new CreateUserHandler($this->em, $this->passwordHasher);
    }

    public function testCreateUserSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())->method('commit');
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush');

        $command = new CreateUserCommand(
            email: 'test@example.com',
            name: 'Test User',
            password: 'Password123',
            roles: ['ROLE_USER'],
            membershipGroup: User::GROUP_STANDARD,
            loanLimit: 5
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(User::class, $result);
    }

    public function testCreateUserWithContactInfo(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())->method('commit');
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush');

        $command = new CreateUserCommand(
            email: 'test@example.com',
            name: 'Test User',
            password: 'Password123',
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
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())->method('commit');
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush');

        $command = new CreateUserCommand(
            email: 'blocked@example.com',
            name: 'Blocked User',
            password: 'Password123',
            roles: ['ROLE_USER'],
            blocked: true,
            blockedReason: 'Policy violation'
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(User::class, $result);
    }

    public function testThrowsExceptionOnInvalidMembershipGroup(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unknown membership group');

        $command = new CreateUserCommand(
            email: 'test@example.com',
            name: 'Test User',
            password: 'Password123',
            roles: ['ROLE_USER'],
            membershipGroup: 'INVALID_GROUP'
        );

        ($this->handler)($command);
    }
}
