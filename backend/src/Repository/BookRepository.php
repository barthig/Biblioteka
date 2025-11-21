<?php
namespace App\Repository;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * Fetch books for public listings with optional advanced filters.
     * @param array{
     *     q?: string,
     *     authorId?: int|null,
     *     categoryId?: int|null,
    *     publisher?: string|null,
    *     resourceType?: string|null,
    *     signature?: string|null,
    *     yearFrom?: int|null,
    *     yearTo?: int|null,
    *     available?: bool|null,
    *     ageGroup?: string|null
     * } $filters
     * @return Book[]
     */
    public function searchPublic(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')
            ->addSelect('a')
            ->leftJoin('b.categories', 'c')
            ->addSelect('c')
            ->orderBy('b.title', 'ASC')
            ->distinct();

        $parameters = [];

        $term = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($term !== '') {
            $lower = function_exists('mb_strtolower') ? mb_strtolower($term) : strtolower($term);
            $normalized = '%' . $lower . '%';
            $qb->andWhere('(' .
                'LOWER(b.title) LIKE :term OR '
                . 'LOWER(a.name) LIKE :term OR '
                . 'LOWER(c.name) LIKE :term OR '
                . 'LOWER(b.isbn) LIKE :term OR '
                . 'LOWER(b.publisher) LIKE :term OR '
                . 'LOWER(b.signature) LIKE :term'
            . ')');
            $parameters['term'] = $normalized;
        }

        if (isset($filters['authorId']) && $filters['authorId'] !== '' && $filters['authorId'] !== null) {
            $qb->andWhere('a.id = :authorId');
            $parameters['authorId'] = (int) $filters['authorId'];
        }

        if (isset($filters['categoryId']) && $filters['categoryId'] !== '' && $filters['categoryId'] !== null) {
            $qb->andWhere('c.id = :categoryId');
            $parameters['categoryId'] = (int) $filters['categoryId'];
        }

        if (array_key_exists('publisher', $filters)) {
            $publisher = trim((string) $filters['publisher']);
            if ($publisher !== '') {
                $qb->andWhere('LOWER(b.publisher) LIKE :publisher');
                $parameters['publisher'] = '%' . (function_exists('mb_strtolower') ? mb_strtolower($publisher) : strtolower($publisher)) . '%';
            }
        }

        if (isset($filters['resourceType']) && $filters['resourceType'] !== '' && $filters['resourceType'] !== null) {
            $qb->andWhere('b.resourceType = :resourceType');
            $parameters['resourceType'] = (string) $filters['resourceType'];
        }

        if (array_key_exists('signature', $filters)) {
            $signature = trim((string) $filters['signature']);
            if ($signature !== '') {
                $qb->andWhere('LOWER(b.signature) LIKE :signature');
                $parameters['signature'] = '%' . (function_exists('mb_strtolower') ? mb_strtolower($signature) : strtolower($signature)) . '%';
            }
        }

        if (isset($filters['yearFrom']) && $filters['yearFrom'] !== null && $filters['yearFrom'] !== '') {
            $qb->andWhere('b.publicationYear >= :yearFrom');
            $parameters['yearFrom'] = (int) $filters['yearFrom'];
        }

        if (isset($filters['yearTo']) && $filters['yearTo'] !== null && $filters['yearTo'] !== '') {
            $qb->andWhere('b.publicationYear <= :yearTo');
            $parameters['yearTo'] = (int) $filters['yearTo'];
        }

        if (isset($filters['ageGroup']) && $filters['ageGroup'] !== '' && $filters['ageGroup'] !== null) {
            $qb->andWhere('b.targetAgeGroup = :ageGroup');
            $parameters['ageGroup'] = (string) $filters['ageGroup'];
        }

        if (array_key_exists('available', $filters)) {
            $value = $filters['available'];
            if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
                $qb->andWhere('b.copies > 0');
            }
        }

        foreach ($parameters as $name => $value) {
            $qb->setParameter($name, $value);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Book[]
     */
    public function findRecommendedByAgeGroup(string $ageGroup, int $limit = 8): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')->addSelect('a')
            ->leftJoin('b.categories', 'c')->addSelect('c')
            ->where('b.targetAgeGroup = :ageGroup')
            ->setParameter('ageGroup', $ageGroup)
            ->orderBy('b.copies', 'DESC')
            ->addOrderBy('b.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit));

        return $qb->getQuery()->getResult();
    }

    /**
     * Convenience wrapper returning all books with default ordering.
     * @return Book[]
     */
    public function findAllForPublic(): array
    {
        return $this->searchPublic();
    }

    /**
     * Expose catalogue facets for building public filters.
     * @return array{
     *   authors: array<int, array{id: int, name: string}>,
     *   categories: array<int, array{id: int, name: string}>,
     *   publishers: string[],
     *   resourceTypes: string[],
     *   years: array{min: int|null, max: int|null}
     * }
     */
    public function getPublicFacets(): array
    {
        $authorRows = $this->createQueryBuilder('b')
            ->select('DISTINCT a.id AS id', 'a.name AS name')
            ->join('b.author', 'a')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $categoryRows = $this->createQueryBuilder('b')
            ->select('DISTINCT c.id AS id', 'c.name AS name')
            ->join('b.categories', 'c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $publisherRows = $this->createQueryBuilder('b')
            ->select('DISTINCT b.publisher AS publisher')
            ->where('b.publisher IS NOT NULL')
            ->orderBy('b.publisher', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $resourceTypeRows = $this->createQueryBuilder('b')
            ->select('DISTINCT b.resourceType AS resourceType')
            ->where('b.resourceType IS NOT NULL')
            ->orderBy('b.resourceType', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $yearBounds = $this->createQueryBuilder('b')
            ->select('MIN(b.publicationYear) AS minYear', 'MAX(b.publicationYear) AS maxYear')
            ->getQuery()
            ->getOneOrNullResult();

        $minYear = null;
        $maxYear = null;
        if (is_array($yearBounds)) {
            if (array_key_exists('minYear', $yearBounds) && $yearBounds['minYear'] !== null) {
                $minYear = (int) $yearBounds['minYear'];
            }
            if (array_key_exists('maxYear', $yearBounds) && $yearBounds['maxYear'] !== null) {
                $maxYear = (int) $yearBounds['maxYear'];
            }
        }

        $ageGroupDefinitions = Book::getAgeGroupDefinitions();

        $ageGroups = [];
        foreach ($ageGroupDefinitions as $value => $definition) {
            $ageGroups[] = [
                'value' => $value,
                'label' => $definition['label'],
                'description' => $definition['description'],
            ];
        }

        return [
            'authors' => array_values(array_map(static fn (array $row) => [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
            ], $authorRows)),
            'categories' => array_values(array_map(static fn (array $row) => [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
            ], $categoryRows)),
            'publishers' => array_values(array_map(static fn (array $row) => (string) $row['publisher'], $publisherRows)),
            'resourceTypes' => array_values(array_map(static fn (array $row) => (string) $row['resourceType'], $resourceTypeRows)),
            'years' => [
                'min' => $minYear,
                'max' => $maxYear,
            ],
            'ageGroups' => $ageGroups,
        ];
    }

    /**
     * @return array<int, array{
     *     bookId: int,
     *     title: string,
     *     totalCopies: int,
     *     availableCopies: int,
     *     totalLoans: int,
     *     lastLoanAt: ?\DateTimeInterface,
     *     activeReservations: int
     * }>
     */
    public function findWeedingCandidates(
        \DateTimeImmutable $cutoff,
        int $minLoans,
        int $limit
    ): array {
        $limit = max(1, $limit);
        $minLoans = max(0, $minLoans);

        $qb = $this->createQueryBuilder('b')
            ->select('b.id AS bookId')
            ->addSelect('b.title AS title')
            ->addSelect('b.totalCopies AS totalCopies')
            ->addSelect('b.copies AS availableCopies')
            ->addSelect('COUNT(l.id) AS totalLoans')
            ->addSelect('MAX(l.borrowedAt) AS lastLoanAt')
            ->addSelect('SUM(CASE WHEN r.status = :activeStatus THEN 1 ELSE 0 END) AS activeReservations')
            ->leftJoin(Loan::class, 'l', 'WITH', 'l.book = b')
            ->leftJoin(Reservation::class, 'r', 'WITH', 'r.book = b')
            ->groupBy('b.id')
            ->having('(MAX(l.borrowedAt) IS NULL OR MAX(l.borrowedAt) <= :cutoff) OR COUNT(l.id) <= :minLoans')
            ->orderBy('totalLoans', 'ASC')
            ->addOrderBy('b.title', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('cutoff', $cutoff)
            ->setParameter('minLoans', $minLoans)
            ->setParameter('activeStatus', Reservation::STATUS_ACTIVE);

        $rows = $qb->getQuery()->getResult();

        return array_map(static function (array $row): array {
            return [
                'bookId' => (int) $row['bookId'],
                'title' => (string) $row['title'],
                'totalCopies' => (int) $row['totalCopies'],
                'availableCopies' => (int) $row['availableCopies'],
                'totalLoans' => (int) $row['totalLoans'],
                'lastLoanAt' => $row['lastLoanAt'] instanceof \DateTimeInterface ? $row['lastLoanAt'] : null,
                'activeReservations' => (int) $row['activeReservations'],
            ];
        }, $rows);
    }
}
