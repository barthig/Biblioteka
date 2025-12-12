<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Loan\ListUserLoansQuery;
use App\Repository\LoanRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListUserLoansHandler
{
    public function __construct(
        private LoanRepository $loanRepository
    ) {
    }

    public function __invoke(ListUserLoansQuery $query): array
    {
        $offset = ($query->page - 1) * $query->limit;

        $qb = $this->loanRepository->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')->addSelect('u')
            ->leftJoin('l.book', 'b')->addSelect('b')
            ->leftJoin('l.bookCopy', 'bc')->addSelect('bc')
            ->where('l.user = :userId')
            ->setParameter('userId', $query->userId)
            ->orderBy('l.borrowedAt', 'DESC');

        $countQb = $this->loanRepository->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.user = :userId')
            ->setParameter('userId', $query->userId);
        
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $loans = $qb->setMaxResults($query->limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return [
            'data' => $loans,
            'meta' => [
                'total' => $total,
                'page' => $query->page,
                'limit' => $query->limit,
                'totalPages' => $total > 0 ? (int)ceil($total / $query->limit) : 0
            ]
        ];
    }
}
