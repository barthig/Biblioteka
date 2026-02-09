<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\CreateOrderCommand;
use App\Entity\AcquisitionOrder;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\AcquisitionBudgetRepository;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateOrderHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SupplierRepository $supplierRepository,
        private readonly AcquisitionBudgetRepository $budgetRepository
    ) {
    }

    public function __invoke(CreateOrderCommand $command): AcquisitionOrder
    {
        $supplier = $this->supplierRepository->find($command->supplierId);
        if (!$supplier) {
            throw NotFoundException::forEntity('Supplier', $command->supplierId);
        }
        if (!$supplier->isActive()) {
            throw BusinessLogicException::invalidState('Supplier is inactive');
        }

        $budget = null;
        if ($command->budgetId !== null) {
            $budget = $this->budgetRepository->find($command->budgetId);
            if (!$budget) {
                throw NotFoundException::forEntity('Budget', $command->budgetId);
            }
            if ($budget->getCurrency() !== $command->currency) {
                throw BusinessLogicException::invalidState('Budget currency does not match order currency');
            }
        }

        $order = new AcquisitionOrder();
        $order->setSupplier($supplier)
            ->setBudget($budget)
            ->setTitle($command->title)
            ->setDescription($command->description)
            ->setReferenceNumber($command->referenceNumber)
            ->setItems($command->items)
            ->setCurrency($command->currency)
            ->setTotalAmount($command->totalAmount);

        if ($command->expectedAt && strtotime($command->expectedAt)) {
            $order->setExpectedAt(new \DateTimeImmutable($command->expectedAt));
        }

        if ($command->status) {
            $status = strtoupper($command->status);
            try {
                if ($status === AcquisitionOrder::STATUS_ORDERED) {
                    $order->markOrdered();
                } elseif ($status === AcquisitionOrder::STATUS_SUBMITTED) {
                    $order->markSubmitted();
                } elseif ($status === AcquisitionOrder::STATUS_RECEIVED) {
                    $order->markReceived();
                } else {
                    $order->setStatus($status);
                }
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::forField('status', 'Invalid status provided');
            }
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}
