<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Acquisition\GetBudgetSummaryQuery;
use App\Repository\AcquisitionBudgetRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetBudgetSummaryHandler
{
    public function __construct(private readonly AcquisitionBudgetRepository $repository)
    {
    }

    public function __invoke(GetBudgetSummaryQuery $query): array
    {
        $budget = $this->repository->find($query->id);
        if (!$budget) {
            throw new \RuntimeException('Budget not found');
        }

        $allocated = round((float) $budget->getAllocatedAmount(), 2);
        $spent = round((float) $budget->getSpentAmount(), 2);
        $remaining = round((float) $budget->remainingAmount(), 2);

        return [
            'id' => $budget->getId(),
            'name' => $budget->getName(),
            'fiscalYear' => $budget->getFiscalYear(),
            'allocatedAmount' => $allocated,
            'spentAmount' => $spent,
            'remainingAmount' => $remaining,
            'currency' => $budget->getCurrency(),
        ];
    }
}
