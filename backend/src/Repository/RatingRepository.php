<?php
declare(strict_types=1);
namespace App\Repository;

use App\Entity\Rating;
use App\Entity\Book;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    /**
     * @return Rating[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Rating[]
     */
    public function findByBook(Book $book): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.book = :book')
            ->setParameter('book', $book)
            ->leftJoin('r.user', 'u')->addSelect('u')
            ->orderBy('r.updatedAt', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUserRatingForBook(User $user, Book $book): ?Rating
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.book = :book')
            ->setParameter('user', $user)
            ->setParameter('book', $book)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getAverageRatingForBook(int $bookId): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avgRating')
            ->where('r.book = :bookId')
            ->setParameter('bookId', $bookId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }

    public function getRatingCountForBook(int $bookId): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.book = :bookId')
            ->setParameter('bookId', $bookId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get books rated highly by user (4-5 stars) for recommendations
     * @return int[]
     */
    public function findHighlyRatedBooksByUser(User $user, int $limit = 10): array
    {
        $ratings = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.book) as bookId')
            ->where('r.user = :user')
            ->andWhere('r.rating >= 4')
            ->setParameter('user', $user)
            ->orderBy('r.rating', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => (int) $r['bookId'], $ratings);
    }

    /**
     * Get rating statistics for multiple books in one query
     * Returns array indexed by book_id with ['avg' => float, 'count' => int]
     * @param int[] $bookIds
     * @return array<int, array{avg: float|null, count: int}>
     */
    public function getRatingStatsForBooks(array $bookIds): array
    {
        if (empty($bookIds)) {
            return [];
        }

        $results = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.book) as bookId', 'AVG(r.rating) as avgRating', 'COUNT(r.id) as ratingCount')
            ->where('r.book IN (:bookIds)')
            ->setParameter('bookIds', $bookIds)
            ->groupBy('r.book')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($results as $row) {
            $stats[(int) $row['bookId']] = [
                'avg' => $row['avgRating'] ? (float) $row['avgRating'] : null,
                'count' => (int) $row['ratingCount']
            ];
        }

        return $stats;
    }
}
