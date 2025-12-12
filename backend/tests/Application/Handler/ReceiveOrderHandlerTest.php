<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Order\ReceiveOrderCommand;
use App\Application\Handler\Command\ReceiveOrderHandler;
use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ReceiveOrderHandlerTest extends TestCase
{
    private OrderRepository $orderRepository;
    private EntityManagerInterface $entityManager;
    private ReceiveOrderHandler $handler;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new ReceiveOrderHandler($this->orderRepository, $this->entityManager);
    }

    public function testReceiveOrderSuccess(): void
    {
        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('setStatus')->with('received');
        
        $this->orderRepository->method('find')->with(1)->willReturn($order);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new ReceiveOrderCommand(orderId: 1);
        $result = ($this->handler)($command);

        $this->assertSame($order, $result);
    }
}
