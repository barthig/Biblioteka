<?php
namespace App\Repository;

use App\Entity\Announcement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AnnouncementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Announcement::class);
    }

    /**
     * Pobiera aktywne ogłoszenia widoczne dla użytkownika
     */
    public function findActiveForUser(?User $user, bool $onlyPinned = false): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.createdBy', 'u')
            ->addSelect('u')
            ->where('a.status = :status')
            ->setParameter('status', 'published')
            ->andWhere('(a.publishedAt IS NULL OR a.publishedAt <= :now)')
            ->andWhere('(a.expiresAt IS NULL OR a.expiresAt > :now)')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.isPinned', 'DESC')
            ->addOrderBy('a.publishedAt', 'DESC');

        if ($onlyPinned) {
            $qb->andWhere('a.isPinned = true');
        }

        $announcements = $qb->getQuery()->getResult();

        // Filtruj po targetAudience
        $filtered = array_filter($announcements, function (Announcement $announcement) use ($user) {
            return $announcement->isVisibleForUser($user);
        });
        
        return $filtered;
    }

    /**
     * Pobiera ogłoszenia dla strony głównej
     */
    public function findForHomepage(?User $user, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.createdBy', 'u')
            ->addSelect('u')
            ->where('a.status = :status')
            ->setParameter('status', 'published')
            ->andWhere('a.showOnHomepage = true')
            ->andWhere('(a.publishedAt IS NULL OR a.publishedAt <= :now)')
            ->andWhere('(a.expiresAt IS NULL OR a.expiresAt > :now)')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.isPinned', 'DESC')
            ->addOrderBy('a.publishedAt', 'DESC')
            ->setMaxResults($limit);

        $announcements = $qb->getQuery()->getResult();

        return array_filter($announcements, function (Announcement $announcement) use ($user) {
            return $announcement->isVisibleForUser($user);
        });
    }

    /**
     * Pobiera wszystkie ogłoszenia (dla administratorów)
     */
    public function findAllWithCreator(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.createdBy', 'u')
            ->addSelect('u')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Pobiera ogłoszenia według statusu
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.createdBy', 'u')
            ->addSelect('u')
            ->where('a.status = :status')
            ->setParameter('status', $status)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Automatycznie archiwizuje wygasłe ogłoszenia
     */
    public function archiveExpired(): int
    {
        return $this->createQueryBuilder('a')
            ->update()
            ->set('a.status', ':archived')
            ->set('a.updatedAt', ':now')
            ->where('a.status = :published')
            ->andWhere('a.expiresAt IS NOT NULL')
            ->andWhere('a.expiresAt < :now')
            ->setParameter('archived', 'archived')
            ->setParameter('published', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
