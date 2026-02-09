<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\UpdateOrderStatusCommand;
use App\Entity\AcquisitionOrder;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\AcquisitionOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateOrderStatusHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AcquisitionOrderRepository $repository
    ) {
    }

    public function __invoke(UpdateOrderStatusCommand $command): AcquisitionOrder
    {
        $order = $this->repository->find($command->id);
        if (!$order) {
            throw NotFoundException::forEntity('Order', $command->id);
        }
        if ($order->getStatus() === AcquisitionOrder::STATUS_CANCELLED) {
            throw BusinessLogicException::invalidState('Cancelled orders cannot be modified');
        }

        $status = strtoupper(trim($command->status));
        switch ($status) {
            case AcquisitionOrder::STATUS_SUBMITTED:
                $order->setStatus(AcquisitionOrder::STATUS_SUBMITTED);
                break;
            case AcquisitionOrder::STATUS_ORDERED:
                $orderedAt = $command->orderedAt && strtotime($command->orderedAt)
                    ? new \DateTimeImmutable($command->orderedAt)
                    : null;
                $order->setStatus(AcquisitionOrder::STATUS_ORDERED);
                $order->setOrderedAt($orderedAt ?? new \DateTimeImmutable());
                break;
            case AcquisitionOrder::STATUS_RECEIVED:
                $receivedAt = $command->receivedAt && strtotime($command->receivedAt)
                    ? new \DateTimeImmutable($command->receivedAt)
                    : null;
                $order->setStatus(AcquisitionOrder::STATUS_RECEIVED);
                $order->setReceivedAt($receivedAt ?? new \DateTimeImmutable());
                break;
            case AcquisitionOrder::STATUS_CANCELLED:
                $order->cancel();
                break;
            case AcquisitionOrder::STATUS_DRAFT:
                $order->setStatus(AcquisitionOrder::STATUS_DRAFT);
                break;
            default:
                throw ValidationException::forField('status', 'Unsupported status: ' . $status);
        }

        if ($command->expectedAt && strtotime($command->expectedAt)) {
            $order->setExpectedAt(new \DateTimeImmutable($command->expectedAt));
        }

        if ($command->totalAmount !== null) {
            $order->setTotalAmount($command->totalAmount);
        }
        if ($command->items !== null) {
            $order->setItems($command->items);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}
