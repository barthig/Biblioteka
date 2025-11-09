<?php
namespace App\Repository;

use App\Entity\Fine;
use App\Entity\Loan;
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
}
