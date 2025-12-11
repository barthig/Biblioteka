<?php
namespace App\Repository;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * @return AuditLog[]
     */
    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')->addSelect('u')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AuditLog[]
     */
    public function findByEntity(string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')->addSelect('u')
            ->where('a.entityType = :type')
            ->andWhere('a.entityId = :id')
            ->setParameter('type', $entityType)
            ->setParameter('id', $entityId)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AuditLog[]
     */
    public function findByUser(User $user, int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AuditLog[]
     */
    public function findByAction(string $action, int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')->addSelect('u')
            ->where('a.action = :action')
            ->setParameter('action', $action)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findWithPagination(int $page = 1, int $limit = 50, ?array $filters = []): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')->addSelect('u')
            ->orderBy('a.createdAt', 'DESC');

        if (isset($filters['entityType'])) {
            $qb->andWhere('a.entityType = :entityType')
               ->setParameter('entityType', $filters['entityType']);
        }

        if (isset($filters['action'])) {
            $qb->andWhere('a.action = :action')
               ->setParameter('action', $filters['action']);
        }

        if (isset($filters['userId'])) {
            $qb->andWhere('a.user = :userId')
               ->setParameter('userId', (int)$filters['userId']);
        }

        $countQb = clone $qb;
        $countQb->select('COUNT(a.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $results = $qb->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return [
            'data' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int)ceil($total / $limit) : 0
            ]
        ];
    }
}
