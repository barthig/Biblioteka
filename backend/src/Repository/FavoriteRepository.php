<?php
namespace App\Repository;

use App\Entity\Book;
use App\Entity\Favorite;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    public function findOneByUserAndBook(User $user, Book $book): ?Favorite
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.book = :book')
            ->setParameter('user', $user)
            ->setParameter('book', $book)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Favorite[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->leftJoin('f.book', 'b')->addSelect('b')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int[]
     */
    public function getBookIdsForUser(User $user): array
    {
        $rows = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.book) AS bookId')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row) => (int) $row['bookId'], $rows);
    }
}
