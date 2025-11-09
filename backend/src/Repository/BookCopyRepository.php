<?php
namespace App\Repository;

use App\Entity\Book;
use App\Entity\BookCopy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookCopyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookCopy::class);
    }

    /**
     * @return BookCopy[]
     */
    public function findAvailableCopies(Book $book, int $limit = 1): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.book = :book')
            ->andWhere('c.status = :status')
            ->setParameter('book', $book)
            ->setParameter('status', BookCopy::STATUS_AVAILABLE)
            ->setMaxResults($limit)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
