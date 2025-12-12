<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\StaffRole\DeleteStaffRoleCommand;
use App\Application\Handler\Command\DeleteStaffRoleHandler;
use App\Entity\StaffRole;
use App\Repository\StaffRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteStaffRoleHandlerTest extends TestCase
{
    private StaffRoleRepository $staffRoleRepository;
    private EntityManagerInterface $entityManager;
    private DeleteStaffRoleHandler $handler;

    protected function setUp(): void
    {
        $this->staffRoleRepository = $this->createMock(StaffRoleRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new DeleteStaffRoleHandler($this->staffRoleRepository, $this->entityManager);
    }

    public function testDeleteStaffRoleSuccess(): void
    {
        $role = $this->createMock(StaffRole::class);
        $this->staffRoleRepository->method('find')->with(1)->willReturn($role);
        $this->entityManager->expects($this->once())->method('remove')->with($role);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new DeleteStaffRoleCommand(roleId: 1);
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
