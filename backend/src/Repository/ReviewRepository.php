<?php
namespace App\Repository;

use App\Entity\Book;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return Review[]
     */
    public function findByBook(Book $book): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.book = :book')
            ->setParameter('book', $book)
            ->orderBy('r.updatedAt', 'DESC')
            ->leftJoin('r.user', 'u')->addSelect('u')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndBook(User $user, Book $book): ?Review
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.book = :book')
            ->setParameter('user', $user)
            ->setParameter('book', $book)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getSummaryForBook(Book $book): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS avgRating', 'COUNT(r.id) AS totalReviews')
            ->andWhere('r.book = :book')
            ->setParameter('book', $book)
            ->getQuery()
            ->getOneOrNullResult();

        $average = null;
        $total = 0;
        if (is_array($result)) {
            if (!empty($result['avgRating'])) {
                $average = round((float) $result['avgRating'], 2);
            }
            if (!empty($result['totalReviews'])) {
                $total = (int) $result['totalReviews'];
            }
        }

        return [
            'average' => $average,
            'total' => $total,
        ];
    }
}
