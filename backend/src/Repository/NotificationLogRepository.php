<?php
declare(strict_types=1);
namespace App\Repository;

use App\Entity\NotificationLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationLog>
 */
class NotificationLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationLog::class);
    }

    public function wasSentSince(string $fingerprint, \DateTimeImmutable $since, ?string $channel = null): bool
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.fingerprint = :fingerprint')
            ->andWhere('n.sentAt >= :since')
            ->setParameter('fingerprint', $fingerprint)
            ->setParameter('since', $since);

        if ($channel !== null) {
            $qb->andWhere('n.channel = :channel')
                ->setParameter('channel', $channel);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function existsForFingerprint(string $fingerprint, string $channel): bool
    {
        $count = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.fingerprint = :fingerprint')
            ->andWhere('n.channel = :channel')
            ->setParameter('fingerprint', $fingerprint)
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /**
     * @return NotificationLog[]
     */
    public function findInAppForUser(int $userId, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.user = :userId')
            ->andWhere('n.channel = :channel')
            ->setParameter('userId', $userId)
            ->setParameter('channel', 'in_app')
            ->orderBy('n.sentAt', 'DESC');

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
