<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Acquisition\UpdateOrderStatusCommand;
use App\Application\Handler\Command\UpdateOrderStatusHandler;
use App\Entity\AcquisitionOrder;
use App\Repository\AcquisitionOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateOrderStatusHandlerTest extends TestCase
{
    private AcquisitionOrderRepository $orderRepository;
    private EntityManagerInterface $entityManager;
    private UpdateOrderStatusHandler $handler;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(AcquisitionOrderRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new UpdateOrderStatusHandler($this->entityManager, $this->orderRepository);
    }

    public function testUpdateOrderStatusSuccess(): void
    {
        $order = $this->createMock(AcquisitionOrder::class);
        $order->method('getStatus')->willReturn('draft');
        $order->expects($this->once())->method('setStatus')->with('DRAFT');
        
        $this->orderRepository->method('find')->with(1)->willReturn($order);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UpdateOrderStatusCommand(
            id: 1,
            status: 'draft',
            orderedAt: null,
            receivedAt: null,
            expectedAt: null,
            totalAmount: null,
            items: null
        );
        $result = ($this->handler)($command);

        $this->assertSame($order, $result);
    }
}
