<?php
namespace App\Repository;

use App\Entity\Book;
use App\Entity\User;
use App\Entity\UserBookInteraction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBookInteraction>
 */
class UserBookInteractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBookInteraction::class);
    }

    /**
     * @return Book[]
     */
    public function findPositiveBooks(User $user, int $minRating = 4): array
    {
        $minRating = max(1, min(5, $minRating));

        return $this->createQueryBuilder('ubi')
            ->select('b')
            ->join('ubi.book', 'b')
            ->where('ubi.user = :user')
            ->andWhere('ubi.type = :liked OR (ubi.rating IS NOT NULL AND ubi.rating >= :minRating)')
            ->setParameter('user', $user)
            ->setParameter('liked', UserBookInteraction::TYPE_LIKED)
            ->setParameter('minRating', $minRating)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int[]
     */
    public function findBookIdsByUser(User $user): array
    {
        $results = $this->createQueryBuilder('ubi')
            ->select('DISTINCT IDENTITY(ubi.book) as bookId')
            ->where('ubi.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return array_values(array_map(static fn (array $row) => (int) $row['bookId'], $results));
    }
}
