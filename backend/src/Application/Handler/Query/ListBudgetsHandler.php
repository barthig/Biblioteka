<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Acquisition\ListBudgetsQuery;
use App\Repository\AcquisitionBudgetRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListBudgetsHandler
{
    public function __construct(private readonly AcquisitionBudgetRepository $repository)
    {
    }

    public function __invoke(ListBudgetsQuery $query): array
    {
        $criteria = [];
        if ($query->year) {
            $criteria['fiscalYear'] = $query->year;
        }

        return $this->repository->findBy($criteria, ['fiscalYear' => 'DESC']);
    }
}
