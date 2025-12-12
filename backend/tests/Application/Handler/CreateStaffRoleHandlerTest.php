<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\StaffRole\CreateStaffRoleCommand;
use App\Application\Handler\Command\CreateStaffRoleHandler;
use App\Entity\StaffRole;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateStaffRoleHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CreateStaffRoleHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new CreateStaffRoleHandler($this->entityManager);
    }

    public function testCreateStaffRoleSuccess(): void
    {
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateStaffRoleCommand(
            name: 'Test Role',
            roleKey: 'test_role',
            modules: ['module1', 'module2'],
            description: 'Test description'
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(StaffRole::class, $result);
    }
}
