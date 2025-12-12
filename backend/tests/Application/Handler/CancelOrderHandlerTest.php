<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Order\CancelOrderCommand;
use App\Application\Handler\Command\CancelOrderHandler;
use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CancelOrderHandlerTest extends TestCase
{
    private OrderRepository $orderRepository;
    private EntityManagerInterface $entityManager;
    private CancelOrderHandler $handler;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new CancelOrderHandler($this->orderRepository, $this->entityManager);
    }

    public function testCancelOrderSuccess(): void
    {
        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('setStatus')->with('cancelled');
        
        $this->orderRepository->method('find')->with(1)->willReturn($order);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CancelOrderCommand(orderId: 1);
        $result = ($this->handler)($command);

        $this->assertSame($order, $result);
    }
}
