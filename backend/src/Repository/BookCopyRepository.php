<?php
declare(strict_types=1);
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

    public function findOneByInventoryCode(string $inventoryCode): ?BookCopy
    {
        return $this->createQueryBuilder('c')
            ->andWhere('UPPER(c.inventoryCode) = :code')
            ->setParameter('code', strtoupper($inventoryCode))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return BookCopy[]
     */
    public function findAvailableCopies(Book $book, int $limit = 1, ?array $accessTypes = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.book = :book')
            ->andWhere('UPPER(c.status) = :status')
            ->setParameter('book', $book)
            ->setParameter('status', BookCopy::STATUS_AVAILABLE)
            ->setMaxResults($limit)
            ->orderBy('c.accessType', 'ASC')
            ->addOrderBy('c.id', 'ASC');

        if ($accessTypes !== null && $accessTypes !== []) {
            $normalized = [];
            foreach ($accessTypes as $type) {
                $type = strtolower(trim((string) $type));
                if ($type === '') {
                    continue;
                }
                switch ($type) {
                    case 'open_stack':
                    case 'open stack':
                        $normalized[] = 'open_stack';
                        $normalized[] = 'open stack';
                        break;
                    case 'storage':
                    case 'closed_stack':
                        $normalized[] = 'storage';
                        $normalized[] = 'closed_stack';
                        break;
                    case 'reference':
                    case 'digital':
                        $normalized[] = 'reference';
                        $normalized[] = 'digital';
                        break;
                    default:
                        $normalized[] = $type;
                        break;
                }
            }
            $normalized = array_values(array_unique($normalized));
            if ($normalized !== []) {
                $qb->andWhere('LOWER(c.accessType) IN (:accessTypes)')
                    ->setParameter('accessTypes', $normalized);
            }
        }

        return $qb->getQuery()->getResult();
    }
}
