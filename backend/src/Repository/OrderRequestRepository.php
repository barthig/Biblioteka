<?php
namespace App\Repository;

use App\Entity\Book;
use App\Entity\OrderRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderRequest::class);
    }

    public function findActiveForUserAndBook(User $user, Book $book): ?OrderRequest
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->andWhere('o.book = :book')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('book', $book)
            ->setParameter('statuses', [OrderRequest::STATUS_PENDING, OrderRequest::STATUS_READY])
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findReadyForUserAndBook(User $user, Book $book): ?OrderRequest
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->andWhere('o.book = :book')
            ->andWhere('o.status = :statusReady OR o.status = :statusPending')
            ->andWhere('o.bookCopy IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('book', $book)
            ->setParameter('statusReady', OrderRequest::STATUS_READY)
            ->setParameter('statusPending', OrderRequest::STATUS_PENDING)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return OrderRequest[]
     */
    public function findActiveByBook(Book $book): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.book = :book')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('book', $book)
            ->setParameter('statuses', [OrderRequest::STATUS_PENDING, OrderRequest::STATUS_READY])
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
