<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\CancelOrderCommand;
use App\Entity\AcquisitionOrder;
use App\Repository\AcquisitionOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CancelOrderHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AcquisitionOrderRepository $repository
    ) {
    }

    public function __invoke(CancelOrderCommand $command): void
    {
        $order = $this->repository->find($command->id);
        if (!$order) {
            throw new \RuntimeException('Order not found');
        }

        if ($order->getStatus() === AcquisitionOrder::STATUS_RECEIVED) {
            throw new \RuntimeException('Order already received');
        }

        $order->cancel();
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }
}
