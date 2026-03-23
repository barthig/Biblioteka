<?php

declare(strict_types=1);

namespace App\Tests\Application\Handler;

use App\Application\Command\User\UpdateUserRolesCommand;
use App\Application\Handler\Command\UpdateUserRolesHandler;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Repository\StaffRoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class UpdateUserRolesHandlerTest extends TestCase
{
    public function testNormalizesAndPersistsValidRoles(): void
    {
        $user = new User();
        $user->setEmail('reader@example.com')->setName('Reader');

        $users = $this->createMock(UserRepository::class);
        $users->method('find')->with(10)->willReturn($user);

        $staffRoles = $this->createMock(StaffRoleRepository::class);
        $staffRoles->method('findOneByRoleKey')->with('ROLE_MEMBER')->willReturn(new \App\Entity\StaffRole());

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($user);
        $em->expects($this->once())->method('flush');

        $handler = new UpdateUserRolesHandler($em, $users, $staffRoles);
        $updated = $handler(new UpdateUserRolesCommand(10, ['user', 'role_member', 'ROLE_USER']));

        self::assertSame(['ROLE_USER', 'ROLE_MEMBER'], $updated->getRoles());
    }

    public function testRejectsUnknownRole(): void
    {
        $user = new User();
        $user->setEmail('reader@example.com')->setName('Reader');

        $users = $this->createMock(UserRepository::class);
        $users->method('find')->with(10)->willReturn($user);

        $staffRoles = $this->createMock(StaffRoleRepository::class);
        $staffRoles->method('findOneByRoleKey')->willReturn(null);

        $handler = new UpdateUserRolesHandler($this->createMock(EntityManagerInterface::class), $users, $staffRoles);

        $this->expectException(ValidationException::class);
        $handler(new UpdateUserRolesCommand(10, ['ROLE_UNKNOWN']));
    }
}
