<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Loan\ListLoansQuery;
use App\Repository\LoanRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListLoansHandler
{
    public function __construct(
        private LoanRepository $loanRepository
    ) {
    }

    public function __invoke(ListLoansQuery $query): array
    {
        $offset = ($query->page - 1) * $query->limit;

        $qb = $this->loanRepository->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')->addSelect('u')
            ->leftJoin('l.book', 'b')->addSelect('b')
            ->leftJoin('l.bookCopy', 'bc')->addSelect('bc')
            ->orderBy('l.borrowedAt', 'DESC');

        // Filter by user if not librarian
        if (!$query->isLibrarian && $query->userId) {
            $qb->where('l.user = :userId')
                ->setParameter('userId', $query->userId);
        }

        // Filter by status
        if ($query->status === 'active') {
            $qb->andWhere('l.returnedAt IS NULL');
        } elseif ($query->status === 'returned') {
            $qb->andWhere('l.returnedAt IS NOT NULL');
        }

        // Filter by overdue
        if ($query->overdue !== null) {
            if ($query->overdue) {
                $qb->andWhere('l.dueAt < :now')
                    ->andWhere('l.returnedAt IS NULL')
                    ->setParameter('now', new \DateTimeImmutable());
            } else {
                $qb->andWhere('l.dueAt >= :now OR l.returnedAt IS NOT NULL')
                    ->setParameter('now', new \DateTimeImmutable());
            }
        }

        // Count total
        $countQb = clone $qb;
        $countQb->select('COUNT(l.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Get paginated results
        $loans = $qb->setMaxResults($query->limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return [
            'data' => $loans,
            'meta' => [
                'page' => $query->page,
                'limit' => $query->limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int)ceil($total / $query->limit) : 0
            ]
        ];
    }
}
