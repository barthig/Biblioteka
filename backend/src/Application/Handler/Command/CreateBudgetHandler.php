<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\CreateBudgetCommand;
use App\Entity\AcquisitionBudget;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateBudgetHandler
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(CreateBudgetCommand $command): AcquisitionBudget
    {
        $budget = (new AcquisitionBudget())
            ->setName($command->name)
            ->setFiscalYear($command->fiscalYear)
            ->setCurrency($command->currency)
            ->setAllocatedAmount($command->allocatedAmount);

        if ($command->spentAmount !== null) {
            $budget->setSpentAmount($command->spentAmount);
        }

        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        return $budget;
    }
}
