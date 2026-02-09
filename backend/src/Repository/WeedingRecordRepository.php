<?php
declare(strict_types=1);
namespace App\Repository;

use App\Entity\WeedingRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeedingRecord>
 */
class WeedingRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeedingRecord::class);
    }

    /**
     * @return WeedingRecord[]
     */
    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.book', 'b')->addSelect('b')
            ->leftJoin('w.bookCopy', 'c')->addSelect('c')
            ->leftJoin('w.processedBy', 'u')->addSelect('u')
            ->orderBy('w.removedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
