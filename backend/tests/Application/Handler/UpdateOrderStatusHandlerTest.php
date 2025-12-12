<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Order\UpdateOrderStatusCommand;
use App\Application\Handler\Command\UpdateOrderStatusHandler;
use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateOrderStatusHandlerTest extends TestCase
{
    private OrderRepository $orderRepository;
    private EntityManagerInterface $entityManager;
    private UpdateOrderStatusHandler $handler;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new UpdateOrderStatusHandler($this->orderRepository, $this->entityManager);
    }

    public function testUpdateOrderStatusSuccess(): void
    {
        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('setStatus')->with('processing');
        
        $this->orderRepository->method('find')->with(1)->willReturn($order);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UpdateOrderStatusCommand(orderId: 1, status: 'processing');
        $result = ($this->handler)($command);

        $this->assertSame($order, $result);
    }
}
