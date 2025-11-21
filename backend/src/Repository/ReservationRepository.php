<?php
namespace App\Repository;

use App\Entity\Book;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\BookCopy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * @return Reservation[]
     */
    public function findActiveByBook(Book $book): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.book = :book')
            ->andWhere('r.status = :status')
            ->setParameter('book', $book)
            ->setParameter('status', Reservation::STATUS_ACTIVE)
            ->orderBy('r.reservedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Reservation[]
     */
    public function findByUser(User $user, bool $includeHistory = false): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.reservedAt', 'DESC');

        if (!$includeHistory) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', Reservation::STATUS_ACTIVE)
                ->orderBy('r.reservedAt', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    public function findFirstActiveForUserAndBook(User $user, Book $book): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.book = :book')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('book', $book)
            ->setParameter('status', Reservation::STATUS_ACTIVE)
            ->orderBy('r.reservedAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByCopy(BookCopy $copy): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.bookCopy = :copy')
            ->andWhere('r.status = :status')
            ->setParameter('copy', $copy)
            ->setParameter('status', Reservation::STATUS_ACTIVE)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countActiveByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.user = :user')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Reservation::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Reservation[]
     */
    public function findReadyForPickup(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.bookCopy IS NOT NULL')
            ->setParameter('status', Reservation::STATUS_ACTIVE)
            ->orderBy('r.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Reservation[]
     */
    public function findExpiredReady(
        \DateTimeImmutable $cutoff,
        ?int $maxResults = null
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.bookCopy IS NOT NULL')
            ->andWhere('r.expiresAt <= :cutoff')
            ->setParameter('status', Reservation::STATUS_ACTIVE)
            ->setParameter('cutoff', $cutoff)
            ->orderBy('r.expiresAt', 'ASC');

        if ($maxResults !== null && $maxResults > 0) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Reservation[]
     */
    public function findExpiringBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.expiresAt BETWEEN :from AND :to')
            ->setParameter('status', Reservation::STATUS_ACTIVE)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('r.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
