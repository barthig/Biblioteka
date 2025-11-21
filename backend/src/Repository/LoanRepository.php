<?php
namespace App\Repository;

use App\Entity\Loan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}
