<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\AddBudgetExpenseCommand;
use App\Entity\AcquisitionExpense;
use App\Repository\AcquisitionBudgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AddBudgetExpenseHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AcquisitionBudgetRepository $budgetRepository
    ) {
    }

    public function __invoke(AddBudgetExpenseCommand $command): AcquisitionExpense
    {
        $budget = $this->budgetRepository->find($command->budgetId);
        if (!$budget) {
            throw new \RuntimeException('Budget not found');
        }

        $expense = (new AcquisitionExpense())
            ->setBudget($budget)
            ->setAmount($command->amount)
            ->setCurrency($budget->getCurrency())
            ->setDescription($command->description);

        try {
            $expense->setType($command->type ?? AcquisitionExpense::TYPE_MISC);
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException('Invalid expense type');
        }

        if ($command->postedAt && strtotime($command->postedAt)) {
            $expense->setPostedAt(new \DateTimeImmutable($command->postedAt));
        }

        $budget->registerExpense($expense->getAmount());

        $this->entityManager->persist($expense);
        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        return $expense;
    }
}
