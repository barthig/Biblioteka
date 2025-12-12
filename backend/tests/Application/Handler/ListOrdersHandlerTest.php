<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListOrdersHandler;
use App\Application\Query\Order\ListOrdersQuery;
use App\Repository\OrderRepository;
use PHPUnit\Framework\TestCase;

class ListOrdersHandlerTest extends TestCase
{
    private OrderRepository $orderRepository;
    private ListOrdersHandler $handler;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->handler = new ListOrdersHandler($this->orderRepository);
    }

    public function testListOrdersSuccess(): void
    {
        $this->orderRepository->method('findBy')->willReturn([]);

        $query = new ListOrdersQuery(page: 1, limit: 50);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
