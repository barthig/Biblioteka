<?php
namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * Fetch all books ordered for public listings.
     * @return Book[]
     */
    public function findAllForPublic(): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')
            ->addSelect('a')
            ->leftJoin('b.categories', 'c')
            ->addSelect('c')
            ->orderBy('b.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Simple search across title, author, category name, and ISBN.
     * @return Book[]
     */
    public function searchPublic(string $term): array
    {
        $normalizedTerm = trim($term);
        if ($normalizedTerm === '') {
            return $this->findAllForPublic();
        }

        $lower = function_exists('mb_strtolower') ? mb_strtolower($normalizedTerm) : strtolower($normalizedTerm);
        $normalized = '%' . $lower . '%';

        return $this->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')
            ->addSelect('a')
            ->leftJoin('b.categories', 'c')
            ->addSelect('c')
            ->where('LOWER(b.title) LIKE :term')
            ->orWhere('LOWER(a.name) LIKE :term')
            ->orWhere('LOWER(c.name) LIKE :term')
            ->orWhere('LOWER(b.isbn) LIKE :term')
            ->setParameter('term', $normalized)
            ->orderBy('b.title', 'ASC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }
}
