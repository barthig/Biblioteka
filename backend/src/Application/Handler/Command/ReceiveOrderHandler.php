<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\ReceiveOrderCommand;
use App\Entity\AcquisitionExpense;
use App\Entity\AcquisitionOrder;
use App\Exception\NotFoundException;
use App\Repository\AcquisitionExpenseRepository;
use App\Repository\AcquisitionOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ReceiveOrderHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AcquisitionOrderRepository $orderRepository,
        private readonly AcquisitionExpenseRepository $expenseRepository
    ) {
    }

    public function __invoke(ReceiveOrderCommand $command): AcquisitionOrder
    {
        $order = $this->orderRepository->find($command->id);
        if (!$order) {
            throw NotFoundException::forEntity('Order', $command->id);
        }

        $receivedAt = $command->receivedAt && strtotime($command->receivedAt)
            ? new \DateTimeImmutable($command->receivedAt)
            : new \DateTimeImmutable();
        $order->markReceived($receivedAt);

        if ($command->totalAmount !== null) {
            $order->setTotalAmount($command->totalAmount);
        }
        if ($command->items !== null) {
            $order->setItems($command->items);
        }

        $this->entityManager->persist($order);

        if ($order->getBudget()) {
            $existingExpense = $this->expenseRepository->findOneBy(['order' => $order]);

            if ($existingExpense) {
                $order->getBudget()->registerExpense('-' . $existingExpense->getAmount());
                $this->entityManager->remove($existingExpense);
            }

            $expenseAmount = $command->expenseAmount ?? $order->getTotalAmount();

            $expense = (new AcquisitionExpense())
                ->setBudget($order->getBudget())
                ->setOrder($order)
                ->setAmount($expenseAmount)
                ->setCurrency($order->getCurrency())
                ->setDescription($command->expenseDescription ?? 'Zakup książek - realizacja zamówienia #' . $order->getId())
                ->setType(AcquisitionExpense::TYPE_ORDER);

            $order->getBudget()->registerExpense($expense->getAmount());

            $this->entityManager->persist($expense);
            $this->entityManager->persist($order->getBudget());
        }

        $this->entityManager->flush();

        return $order;
    }
}
