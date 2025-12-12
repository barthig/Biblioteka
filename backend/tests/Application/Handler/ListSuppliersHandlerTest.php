<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListSuppliersHandler;
use App\Application\Query\Supplier\ListSuppliersQuery;
use App\Repository\SupplierRepository;
use PHPUnit\Framework\TestCase;

class ListSuppliersHandlerTest extends TestCase
{
    private SupplierRepository $supplierRepository;
    private ListSuppliersHandler $handler;

    protected function setUp(): void
    {
        $this->supplierRepository = $this->createMock(SupplierRepository::class);
        $this->handler = new ListSuppliersHandler($this->supplierRepository);
    }

    public function testListSuppliersSuccess(): void
    {
        $this->supplierRepository->method('findBy')->willReturn([]);

        $query = new \App\Application\Query\Acquisition\ListSuppliersQuery();
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
