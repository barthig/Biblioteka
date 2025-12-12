<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\GetStaffRoleHandler;
use App\Application\Query\StaffRole\GetStaffRoleQuery;
use App\Entity\StaffRole;
use App\Repository\StaffRoleRepository;
use PHPUnit\Framework\TestCase;

class GetStaffRoleHandlerTest extends TestCase
{
    private StaffRoleRepository $staffRoleRepository;
    private GetStaffRoleHandler $handler;

    protected function setUp(): void
    {
        $this->staffRoleRepository = $this->createMock(StaffRoleRepository::class);
        $this->handler = new GetStaffRoleHandler($this->staffRoleRepository);
    }

    public function testGetStaffRoleSuccess(): void
    {
        $role = $this->createMock(StaffRole::class);
        $this->staffRoleRepository->method('find')->with(1)->willReturn($role);

        $query = new GetStaffRoleQuery(roleId: 1);
        $result = ($this->handler)($query);

        $this->assertSame($role, $result);
    }
}
