<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Report\GetUsageReportQuery;
use App\Entity\Loan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetUsageReportHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(GetUsageReportQuery $query): ?array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(l.id) AS totalLoans', 'SUM(CASE WHEN l.returnedAt IS NULL THEN 1 ELSE 0 END) AS activeLoans')
            ->from(Loan::class, 'l');

        if ($query->from) {
            $qb->andWhere('l.borrowedAt >= :from')->setParameter('from', new \DateTimeImmutable($query->from));
        }
        if ($query->to) {
            $qb->andWhere('l.borrowedAt <= :to')->setParameter('to', new \DateTimeImmutable($query->to));
        }

        $result = $qb->getQuery()->getSingleResult();
        $total = (int)$result['totalLoans'];
        $active = (int)$result['activeLoans'];

        if ($total === 0) {
            return null;
        }

        return [
            'totalLoans' => $total,
            'activeLoans' => $active,
            'from' => $query->from,
            'to' => $query->to,
        ];
    }
}
