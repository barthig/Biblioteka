<?php
declare(strict_types=1);
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
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

    /**
     * Search users by name, email, PESEL or card number
     * @return User[]
     */
    public function searchUsers(string $query, int $limit = 20): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($query));
        if ($normalized === '' || strlen($normalized) < 2) {
            return [];
        }

        $needle = '%' . mb_strtolower($normalized, 'UTF-8') . '%';
        $qb = $this->createQueryBuilder('u')
            ->where('LOWER(u.name) LIKE :query')
            ->orWhere('LOWER(u.email) LIKE :query')
            ->orWhere('LOWER(u.pesel) LIKE :query')
            ->orWhere('LOWER(u.cardNumber) LIKE :query')
            ->setParameter('query', $needle)
            ->setMaxResults($limit)
            ->orderBy('u.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return User[]
     */
    public function findAnnouncementRecipients(int $limit = 0): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.emailAnnouncements = true')
            ->andWhere('u.blocked = false')
            ->andWhere('u.verified = true')
            ->andWhere('u.email IS NOT NULL')
            ->orderBy('u.updatedAt', 'DESC');

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function save(User $user, bool $flush = false): void
    {
        $this->getEntityManager()->persist($user);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $user, bool $flush = false): void
    {
        $this->getEntityManager()->remove($user);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
