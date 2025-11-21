<?php
namespace App\Repository;

use App\Entity\NotificationLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}
