<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\UpdateBudgetCommand;
use App\Entity\AcquisitionBudget;
use App\Repository\AcquisitionBudgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateBudgetHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AcquisitionBudgetRepository $repository
    ) {
    }

    public function __invoke(UpdateBudgetCommand $command): AcquisitionBudget
    {
        $budget = $this->repository->find($command->id);
        if (!$budget) {
            throw new \RuntimeException('Budget not found');
        }

        if ($command->name !== null) {
            $budget->setName($command->name);
        }
        if ($command->fiscalYear !== null) {
            $budget->setFiscalYear($command->fiscalYear);
        }
        if ($command->allocatedAmount !== null) {
            $budget->setAllocatedAmount($command->allocatedAmount);
        }
        if ($command->currency !== null) {
            $budget->setCurrency($command->currency);
        }

        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        return $budget;
    }
}
