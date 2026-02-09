<?php
declare(strict_types=1);
namespace App\Repository;

use App\Entity\Book;
use App\Entity\BookDigitalAsset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookDigitalAssetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookDigitalAsset::class);
    }

    /**
     * @return BookDigitalAsset[]
     */
    public function findForBook(Book $book): array
    {
        return $this->createQueryBuilder('asset')
            ->andWhere('asset.book = :book')
            ->setParameter('book', $book)
            ->orderBy('asset.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
