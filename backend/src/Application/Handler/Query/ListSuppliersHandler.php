<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Acquisition\ListSuppliersQuery;
use App\Repository\SupplierRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListSuppliersHandler
{
    public function __construct(private readonly SupplierRepository $repository)
    {
    }

    public function __invoke(ListSuppliersQuery $query): array
    {
        $criteria = [];
        if ($query->active !== null) {
            $criteria['active'] = $query->active;
        }

        $items = $this->repository->findBy($criteria, ['name' => 'ASC']);
        
        return ['items' => $items];
    }
}
