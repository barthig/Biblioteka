<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Acquisition\ReceiveOrderCommand;
use App\Application\Handler\Command\ReceiveOrderHandler;
use App\Entity\AcquisitionOrder;
use App\Repository\AcquisitionOrderRepository;
use App\Repository\AcquisitionExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ReceiveOrderHandlerTest extends TestCase
{
    private AcquisitionOrderRepository $orderRepository;
    private AcquisitionExpenseRepository $expenseRepository;
    private EntityManagerInterface $entityManager;
    private ReceiveOrderHandler $handler;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(AcquisitionOrderRepository::class);
        $this->expenseRepository = $this->createMock(AcquisitionExpenseRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new ReceiveOrderHandler($this->entityManager, $this->orderRepository, $this->expenseRepository);
    }

    public function testReceiveOrderSuccess(): void
    {
        $order = $this->createMock(AcquisitionOrder::class);
        $order->expects($this->once())->method('markReceived');
        $order->method('getBudget')->willReturn(null);
        
        $this->orderRepository->method('find')->with(1)->willReturn($order);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new ReceiveOrderCommand(
            id: 1,
            receivedAt: '2024-01-15',
            totalAmount: '500.00',
            items: [],
            expenseAmount: '500.00',
            expenseDescription: 'Test expense'
        );
        $result = ($this->handler)($command);

        $this->assertSame($order, $result);
    }
}
