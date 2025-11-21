<?php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[]
     */
    public function findNewsletterRecipients(int $limit = 0): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.newsletterSubscribed = :subscribed')
            ->andWhere('u.blocked = false')
            ->andWhere('u.verified = true')
            ->andWhere('u.email IS NOT NULL')
            ->setParameter('subscribed', true)
            ->orderBy('u.updatedAt', 'DESC');

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
