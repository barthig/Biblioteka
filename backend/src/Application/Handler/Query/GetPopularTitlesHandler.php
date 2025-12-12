<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Report\GetPopularTitlesQuery;
use App\Entity\Loan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetPopularTitlesHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(GetPopularTitlesQuery $query): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('b.id AS bookId', 'b.title AS title', 'COUNT(l.id) AS loanCount')
            ->from(Loan::class, 'l')
            ->join('l.book', 'b')
            ->groupBy('b.id')
            ->orderBy('loanCount', 'DESC')
            ->setMaxResults($query->limit);

        if ($query->days > 0) {
            $since = (new \DateTimeImmutable())->modify(sprintf('-%d days', $query->days));
            $qb->andWhere('l.borrowedAt >= :since')->setParameter('since', $since);
        }

        $raw = $qb->getQuery()->getResult();
        $items = array_map(static function (array $row): array {
            return [
                'bookId' => (int) $row['bookId'],
                'title' => $row['title'],
                'loanCount' => (int) $row['loanCount'],
            ];
        }, $raw);

        return [
            'limit' => $query->limit,
            'periodDays' => $query->days,
            'items' => $items,
        ];
    }
}
