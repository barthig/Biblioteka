<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Report\GetInventoryOverviewQuery;
use App\Entity\BookCopy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetInventoryOverviewHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(GetInventoryOverviewQuery $query): array
    {
        $statusBreakdown = $this->entityManager->createQueryBuilder()
            ->select('c.status AS status', 'COUNT(c.id) AS total')
            ->from(BookCopy::class, 'c')
            ->groupBy('c.status')
            ->getQuery()
            ->getResult();

        $breakdown = array_map(static function (array $row): array {
            return [
                'status' => $row['status'],
                'total' => (int) $row['total'],
            ];
        }, $statusBreakdown);

        $totalCopies = array_sum(array_map(static fn ($row) => $row['total'], $breakdown));
        $borrowed = 0;
        foreach ($breakdown as $row) {
            if ($row['status'] === BookCopy::STATUS_BORROWED) {
                $borrowed = (int) $row['total'];
                break;
            }
        }

        $utilization = $totalCopies > 0 ? round(100 * $borrowed / $totalCopies, 2) : 0.0;

        return [
            'copies' => $breakdown,
            'totalCopies' => $totalCopies,
            'borrowedPercentage' => $utilization,
        ];
    }
}
