<?php
namespace App\Repository;

use App\Entity\Fine;
use App\Entity\Loan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fine>
 */
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
            ->select('SUM(f.amount) as total')
            ->join('f.loan', 'l')
            ->andWhere('l.user = :user')
            ->andWhere('f.paidAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($total ?? 0.0);
    }

    /**
     * @return Fine[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.loan', 'l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Fine[]
     */
    public function findOutstandingByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.loan', 'l')
            ->andWhere('l.user = :user')
            ->andWhere('f.paidAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndUser(int $id, User $user): ?Fine
    {
        return $this->createQueryBuilder('f')
            ->join('f.loan', 'l')
            ->andWhere('f.id = :id')
            ->andWhere('l.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param float $minimum
     * @return int[] user IDs that meet or exceed the outstanding threshold
     */
    public function getUserIdsWithOutstandingAtLeast(float $minimum): array
    {
        $rows = $this->createQueryBuilder('f')
            ->select('IDENTITY(l.user) AS userId', 'SUM(f.amount) AS total')
            ->join('f.loan', 'l')
            ->andWhere('f.paidAt IS NULL')
            ->groupBy('l.user')
            ->having('SUM(f.amount) >= :minimum')
            ->setParameter('minimum', $minimum)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row) => (int) $row['userId'], $rows);
    }

    /**
     * @param int[] $userIds
     * @return array<int, float> map of userId => outstanding amount
     */
    public function getOutstandingTotalsForUsers(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('f')
            ->select('IDENTITY(l.user) AS userId', 'SUM(f.amount) AS total')
            ->join('f.loan', 'l')
            ->andWhere('f.paidAt IS NULL')
            ->andWhere('l.user IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->groupBy('l.user')
            ->getQuery()
            ->getScalarResult();

        $totals = [];
        foreach ($rows as $row) {
            $totals[(int) $row['userId']] = (float) $row['total'];
        }

        return $totals;
    }
}
