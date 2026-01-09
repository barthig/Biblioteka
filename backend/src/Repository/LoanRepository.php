<?php
namespace App\Repository;

use App\Entity\Loan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Loan>
 */
class LoanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

    public function findActiveByInventoryCode(string $inventoryCode): ?Loan
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.bookCopy', 'c')
            ->addSelect('c')
            ->andWhere('c.inventoryCode = :code')
            ->andWhere('l.returnedAt IS NULL')
            ->setParameter('code', $inventoryCode)
            ->orderBy('l.borrowedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countActiveByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.user = :user')
            ->andWhere('l.returnedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Loan[]
     */
    public function findDueBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.returnedAt IS NULL')
            ->andWhere('l.dueAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('l.dueAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Loan[]
     */
    public function findOverdueSince(\DateTimeImmutable $since, ?int $maxResults = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->andWhere('l.returnedAt IS NULL')
            ->andWhere('l.dueAt <= :since')
            ->setParameter('since', $since)
            ->orderBy('l.dueAt', 'ASC');

        if ($maxResults !== null && $maxResults > 0) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    public function hasOverdueLongerThan(User $user, \DateTimeImmutable $cutoff): bool
    {
        $count = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.user = :user')
            ->andWhere('l.returnedAt IS NULL')
            ->andWhere('l.dueAt <= :cutoff')
            ->setParameter('user', $user)
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $count) > 0;
    }

    /**
     * @return Loan[]
     */
    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.book', 'b')->addSelect('b')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->addSelect('CASE WHEN l.returnedAt IS NOT NULL THEN l.returnedAt ELSE l.borrowedAt END as HIDDEN sortDate')
            ->orderBy('sortDate', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int[]
     */
    public function getUserIdsWithOverdueSince(\DateTimeImmutable $cutoff): array
    {
        $rows = $this->createQueryBuilder('l')
            ->select('DISTINCT IDENTITY(l.user) AS userId')
            ->andWhere('l.returnedAt IS NULL')
            ->andWhere('l.dueAt <= :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row) => (int) $row['userId'], $rows);
    }

    /**
     * Count active loans (not returned yet).
     */
    public function countActiveLoans(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.returnedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count overdue loans (past due date and not returned).
     */
    public function countOverdueLoans(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.returnedAt IS NULL')
            ->andWhere('l.dueAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
