<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\StaffRole\UpdateStaffRoleCommand;
use App\Application\Handler\Command\UpdateStaffRoleHandler;
use App\Entity\StaffRole;
use App\Repository\StaffRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateStaffRoleHandlerTest extends TestCase
{
    private StaffRoleRepository $staffRoleRepository;
    private EntityManagerInterface $entityManager;
    private UpdateStaffRoleHandler $handler;

    protected function setUp(): void
    {
        $this->staffRoleRepository = $this->createMock(StaffRoleRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new UpdateStaffRoleHandler($this->staffRoleRepository, $this->entityManager);
    }

    public function testUpdateStaffRoleSuccess(): void
    {
        $role = $this->createMock(StaffRole::class);
        $role->expects($this->once())->method('setName')->with('Updated Role');
        
        $this->staffRoleRepository->method('find')->with(1)->willReturn($role);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UpdateStaffRoleCommand(roleId: 1, name: 'Updated Role');
        $result = ($this->handler)($command);

        $this->assertSame($role, $result);
    }
}
