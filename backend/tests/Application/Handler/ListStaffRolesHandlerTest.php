<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListStaffRolesHandler;
use App\Application\Query\StaffRole\ListStaffRolesQuery;
use App\Repository\StaffRoleRepository;
use PHPUnit\Framework\TestCase;

class ListStaffRolesHandlerTest extends TestCase
{
    private StaffRoleRepository $staffRoleRepository;
    private ListStaffRolesHandler $handler;

    protected function setUp(): void
    {
        $this->staffRoleRepository = $this->createMock(StaffRoleRepository::class);
        $this->handler = new ListStaffRolesHandler($this->staffRoleRepository);
    }

    public function testListStaffRolesSuccess(): void
    {
        $this->staffRoleRepository->method('findBy')->willReturn([]);

        $query = new ListStaffRolesQuery(page: 1, limit: 50);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
