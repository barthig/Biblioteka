<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Acquisition\CancelOrderCommand;
use App\Application\Handler\Command\CancelOrderHandler;
use App\Entity\AcquisitionOrder;
use App\Repository\AcquisitionOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CancelOrderHandlerTest extends TestCase
{
    private AcquisitionOrderRepository $orderRepository;
    private EntityManagerInterface $entityManager;
    private CancelOrderHandler $handler;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(AcquisitionOrderRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new CancelOrderHandler($this->entityManager, $this->orderRepository);
    }

    public function testCancelOrderSuccess(): void
    {
        $order = $this->createMock(AcquisitionOrder::class);
        $order->method('getStatus')->willReturn('pending');
        $order->expects($this->once())->method('cancel');
        
        $this->orderRepository->method('find')->with(1)->willReturn($order);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CancelOrderCommand(id: 1);
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
