<?php
namespace App\Repository;

use App\Entity\Fine;
use App\Entity\Loan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fine::class);
    }

    /**
     * @return Fine[]
     */
    public function findByLoan(Loan $loan): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.loan = :loan')
            ->setParameter('loan', $loan)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveOverdueFine(Loan $loan): ?Fine
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.loan = :loan')
            ->andWhere('f.paidAt IS NULL')
            ->andWhere('f.reason LIKE :reason')
            ->setParameter('loan', $loan)
            ->setParameter('reason', 'Przetrzymanie%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function sumOutstandingByUser(User $user): float
    {
        $total = $this->createQueryBuilder('f')
            ->select('COALESCE(SUM(f.amount), 0) as total')
            ->join('f.loan', 'l')
            ->andWhere('l.user = :user')
            ->andWhere('f.paidAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $total;
    }
}
