<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\CancelOrderCommand;
use App\Entity\AcquisitionOrder;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Repository\AcquisitionOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
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
            throw NotFoundException::forEntity('Order', $command->id);
        }

        if ($order->getStatus() === AcquisitionOrder::STATUS_RECEIVED) {
            throw BusinessLogicException::invalidState('Cannot cancel an already received order');
        }

        $order->cancel();
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }
}
