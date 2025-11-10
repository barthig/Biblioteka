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
}
