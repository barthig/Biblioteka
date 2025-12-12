<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\CreateOrderCommand;
use App\Entity\AcquisitionOrder;
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
            throw new \RuntimeException('Supplier not found');
        }
        if (!$supplier->isActive()) {
            throw new \RuntimeException('Supplier is inactive');
        }

        $budget = null;
        if ($command->budgetId !== null) {
            $budget = $this->budgetRepository->find($command->budgetId);
            if (!$budget) {
                throw new \RuntimeException('Budget not found');
            }
            if ($budget->getCurrency() !== $command->currency) {
                throw new \RuntimeException('Budget currency mismatch');
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
                throw new \RuntimeException('Invalid status provided');
            }
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}
