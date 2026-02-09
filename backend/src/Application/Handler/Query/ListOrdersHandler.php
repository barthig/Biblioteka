<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Acquisition\ListOrdersQuery;
use App\Repository\AcquisitionOrderRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListOrdersHandler
{
    public function __construct(private readonly AcquisitionOrderRepository $repository)
    {
    }

    public function __invoke(ListOrdersQuery $query): array
    {
        $offset = ($query->page - 1) * $query->limit;
        
        $qb = $this->repository->createQueryBuilder('o')
            ->leftJoin('o.supplier', 's')->addSelect('s')
            ->leftJoin('o.budget', 'b')->addSelect('b')
            ->orderBy('o.createdAt', 'DESC');

        if ($query->status !== null) {
            $qb->andWhere('o.status = :status')->setParameter('status', $query->status);
        }

        if ($query->supplierId !== null) {
            $qb->andWhere('o.supplier = :supplierId')->setParameter('supplierId', $query->supplierId);
        }

        if ($query->budgetId !== null) {
            $qb->andWhere('o.budget = :budgetId')->setParameter('budgetId', $query->budgetId);
        }

        // Count total (without orderBy for COUNT query)
        $countQb = $this->repository->createQueryBuilder('o');
        
        if ($query->status !== null) {
            $countQb->andWhere('o.status = :status')->setParameter('status', $query->status);
        }

        if ($query->supplierId !== null) {
            $countQb->andWhere('o.supplier = :supplierId')->setParameter('supplierId', $query->supplierId);
        }

        if ($query->budgetId !== null) {
            $countQb->andWhere('o.budget = :budgetId')->setParameter('budgetId', $query->budgetId);
        }
        
        $countQb->select('COUNT(o.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $orders = $qb->setMaxResults($query->limit)->setFirstResult($offset)->getQuery()->getResult();
        
        return [
            'data' => $orders,
            'meta' => [
                'page' => $query->page,
                'limit' => $query->limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int)ceil($total / $query->limit) : 0
            ]
        ];
    }
}
