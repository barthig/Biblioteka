<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Report\GetPatronSegmentsQuery;
use App\Entity\User;
use App\Entity\Loan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetPatronSegmentsHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(GetPatronSegmentsQuery $query): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('u.membershipGroup AS membershipGroup')
            ->addSelect('COUNT(u.id) AS totalUsers')
            ->addSelect('SUM(CASE WHEN u.blocked = true THEN 1 ELSE 0 END) AS blockedUsers')
            ->addSelect('AVG(u.loanLimit) AS avgLoanLimit')
            ->addSelect('COUNT(DISTINCT l.id) AS activeLoans')
            ->from(User::class, 'u')
            ->leftJoin(Loan::class, 'l', 'WITH', 'l.user = u AND l.returnedAt IS NULL')
            ->groupBy('u.membershipGroup')
            ->orderBy('membershipGroup', 'ASC');

        $segments = array_map(static function (array $row): array {
            return [
                'membershipGroup' => $row['membershipGroup'],
                'totalUsers' => (int) $row['totalUsers'],
                'blockedUsers' => (int) $row['blockedUsers'],
                'activeLoans' => (int) $row['activeLoans'],
                'avgLoanLimit' => round((float) $row['avgLoanLimit'], 2),
            ];
        }, $qb->getQuery()->getResult());

        return ['segments' => $segments];
    }
}
