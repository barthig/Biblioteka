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
    *     ageGroup?: string|null,
    *     page?: int,
    *     limit?: int
     * } $filters
     * @return array{data: Book[], meta: array{page: int, limit: int, total: int, totalPages: int}}
     */
    public function searchPublic(array $filters = []): array
    {
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $limit = isset($filters['limit']) ? min(100, max(10, (int)$filters['limit'])) : 20;
        $offset = ($page - 1) * $limit;

        // Najpierw pobierz IDs książek spełniających kryteria
        $idsQb = $this->createQueryBuilder('b')
            ->select('b.id')
            ->leftJoin('b.author', 'a')
            ->leftJoin('b.categories', 'c')
            ->groupBy('b.id, a.id, c.id');

        $parameters = [];

        $term = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($term !== '') {
            $lower = function_exists('mb_strtolower') ? mb_strtolower($term) : strtolower($term);
            $normalized = '%' . $lower . '%';
            $idsQb->andWhere('(' .
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
            $idsQb->andWhere('a.id = :authorId');
            $parameters['authorId'] = (int) $filters['authorId'];
        }

        if (isset($filters['categoryId']) && $filters['categoryId'] !== '' && $filters['categoryId'] !== null) {
            $idsQb->andWhere('c.id = :categoryId');
            $parameters['categoryId'] = (int) $filters['categoryId'];
        }

        if (array_key_exists('publisher', $filters)) {
            $publisher = trim((string) $filters['publisher']);
            if ($publisher !== '') {
                $idsQb->andWhere('LOWER(b.publisher) LIKE :publisher');
                $parameters['publisher'] = '%' . (function_exists('mb_strtolower') ? mb_strtolower($publisher) : strtolower($publisher)) . '%';
            }
        }

        if (isset($filters['resourceType']) && $filters['resourceType'] !== '' && $filters['resourceType'] !== null) {
            $idsQb->andWhere('b.resourceType = :resourceType');
            $parameters['resourceType'] = (string) $filters['resourceType'];
        }

        if (array_key_exists('signature', $filters)) {
            $signature = trim((string) $filters['signature']);
            if ($signature !== '') {
                $idsQb->andWhere('LOWER(b.signature) LIKE :signature');
                $parameters['signature'] = '%' . (function_exists('mb_strtolower') ? mb_strtolower($signature) : strtolower($signature)) . '%';
            }
        }

        if (isset($filters['yearFrom']) && $filters['yearFrom'] !== null && $filters['yearFrom'] !== '') {
            $idsQb->andWhere('b.publicationYear >= :yearFrom');
            $parameters['yearFrom'] = (int) $filters['yearFrom'];
        }

        if (isset($filters['yearTo']) && $filters['yearTo'] !== null && $filters['yearTo'] !== '') {
            $idsQb->andWhere('b.publicationYear <= :yearTo');
            $parameters['yearTo'] = (int) $filters['yearTo'];
        }

        if (isset($filters['ageGroup']) && $filters['ageGroup'] !== '' && $filters['ageGroup'] !== null) {
            $idsQb->andWhere('b.targetAgeGroup = :ageGroup');
            $parameters['ageGroup'] = (string) $filters['ageGroup'];
        }

        if (array_key_exists('available', $filters)) {
            $value = $filters['available'];
            if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
                $idsQb->andWhere('b.copies > 0');
            }
        }

        foreach ($parameters as $name => $value) {
            $idsQb->setParameter($name, $value);
        }

        // Policz total
        $countQb = clone $idsQb;
        $countQb->select('COUNT(DISTINCT b.id)');
        $countQb->resetDQLPart('groupBy');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Sortuj i paginuj IDs
        $idsQb->orderBy('b.title', 'ASC')
              ->setMaxResults($limit)
              ->setFirstResult($offset);
        
        $paginatedIds = array_column($idsQb->getQuery()->getScalarResult(), 'id');

        // Teraz pobierz pełne obiekty książek dla tych IDs
        if (empty($paginatedIds)) {
            $results = [];
        } else {
            $results = $this->createQueryBuilder('b')
                ->leftJoin('b.author', 'a')->addSelect('a')
                ->leftJoin('b.categories', 'c')->addSelect('c')
                ->where('b.id IN (:ids)')
                ->setParameter('ids', $paginatedIds)
                ->orderBy('b.title', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return [
            'data' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int)ceil($total / $limit) : 0
            ]
        ];
    }

    /**
     * @return Book[]
     */
    public function findRecommendedByAgeGroup(string $ageGroup, int $limit = 8, array $excludeBookIds = []): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')->addSelect('a')
            ->leftJoin('b.categories', 'c')->addSelect('c')
            ->where('b.targetAgeGroup = :ageGroup')
            ->setParameter('ageGroup', $ageGroup)
            ->groupBy('b.id, a.id')
            ->orderBy('b.copies', 'DESC')
            ->addOrderBy('b.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit));

        if (!empty($excludeBookIds)) {
            $qb->andWhere('b.id NOT IN (:excludeIds)')
               ->setParameter('excludeIds', $excludeBookIds);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Suggest books that match a set of preferred authors or categories, optionally scoped by age group.
     *
     * @param int[] $authorIds
     * @param int[] $categoryIds
     * @param int[] $excludeBookIds
     * @return Book[]
     */
    public function findRecommendedByPreferences(
        array $authorIds,
        array $categoryIds,
        ?string $ageGroup,
        array $excludeBookIds,
        int $limit = 8
    ): array {
        if (empty($authorIds) && empty($categoryIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')->addSelect('a')
            ->leftJoin('b.categories', 'c')->addSelect('c')
            ->groupBy('b.id, a.id, c.id')
            ->orderBy('b.copies', 'DESC')
            ->addOrderBy('b.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit));

        $conditions = [];

        if (!empty($authorIds)) {
            $conditions[] = 'a.id IN (:authorIds)';
            $qb->setParameter('authorIds', $authorIds);
        }

        if (!empty($categoryIds)) {
            $conditions[] = 'c.id IN (:categoryIds)';
            $qb->setParameter('categoryIds', $categoryIds);
        }

        if (!empty($conditions)) {
            $qb->andWhere('(' . implode(' OR ', $conditions) . ')');
        }

        if ($ageGroup !== null) {
            $qb->andWhere('b.targetAgeGroup = :ageGroup')
                ->setParameter('ageGroup', $ageGroup);
        }

        if (!empty($excludeBookIds)) {
            $qb->andWhere('b.id NOT IN (:excludeIds)')
                ->setParameter('excludeIds', $excludeBookIds);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find top N most borrowed books across all time.
     * @return Book[]
     */
    public function findMostBorrowedBooks(int $limit = 10, array $excludeBookIds = []): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('App\Entity\Loan', 'l', 'WITH', 'l.book = b')
            ->addSelect('a')
            ->leftJoin('b.author', 'a')
            ->leftJoin('b.categories', 'c')
            ->groupBy('b.id, a.id, c.id')
            ->addSelect('COUNT(l.id) as HIDDEN loanCount')
            ->orderBy('loanCount', 'DESC')
            ->addOrderBy('b.title', 'ASC')
            ->setMaxResults($limit);

        if (!empty($excludeBookIds)) {
            $qb->andWhere('b.id NOT IN (:excludeIds)')
                ->setParameter('excludeIds', $excludeBookIds);
        }

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
            ->select('a.id AS id', 'a.name AS name')
            ->join('b.author', 'a')
            ->groupBy('a.id', 'a.name')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $categoryRows = $this->createQueryBuilder('b')
            ->select('c.id AS id', 'c.name AS name')
            ->join('b.categories', 'c')
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $publisherRows = $this->createQueryBuilder('b')
            ->select('b.publisher AS publisher')
            ->where('b.publisher IS NOT NULL')
            ->groupBy('b.publisher')
            ->orderBy('b.publisher', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $resourceTypeRows = $this->createQueryBuilder('b')
            ->select('b.resourceType AS resourceType')
            ->where('b.resourceType IS NOT NULL')
            ->groupBy('b.resourceType')
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
     * @return Book[]
     */
    public function findNewArrivals(\DateTimeImmutable $since, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('b')
            ->andWhere('b.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('b.createdAt', 'DESC')
            ->addOrderBy('b.id', 'DESC')
            ->setMaxResults(max(1, $limit));

        return $qb->getQuery()->getResult();
    }

    /**
     * Get popular books based on loan count
     * @return Book[]
     */
    public function findPopularBooks(int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')
            ->leftJoin('b.categories', 'c')
            ->leftJoin(Loan::class, 'l', 'WITH', 'l.book = b')
            ->groupBy('b.id, a.id, c.id')
            ->orderBy('COUNT(l.id)', 'DESC')
            ->addOrderBy('b.title', 'ASC')
            ->setMaxResults(max(1, $limit));

        return $qb->getQuery()->getResult();
    }

    /**
     * Get newest books (recently added to catalog)
     * @return Book[]
     */
    public function findNewestBooks(int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('b')
            ->orderBy('b.createdAt', 'DESC')
            ->addOrderBy('b.id', 'DESC')
            ->setMaxResults(max(1, $limit));

        return $qb->getQuery()->getResult();
    }

    /**
     * Get availability information for a book
     * @return array{totalCopies: int, availableCopies: int, borrowedCopies: int, reservations: int}
     */
    public function getBookAvailability(int $bookId): ?array
    {
        $book = $this->find($bookId);
        if (!$book) {
            return null;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(r.id) as reservationCount')
            ->from(Reservation::class, 'r')
            ->where('r.book = :book')
            ->andWhere('r.status = :status')
            ->setParameter('book', $book)
            ->setParameter('status', Reservation::STATUS_ACTIVE);

        $reservationCount = (int) $qb->getQuery()->getSingleScalarResult();

        return [
            'totalCopies' => $book->getTotalCopies(),
            'availableCopies' => $book->getCopies(),
            'borrowedCopies' => $book->getTotalCopies() - $book->getCopies(),
            'reservations' => $reservationCount
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
            ->leftJoin('b.author', 'a')
            ->leftJoin('b.categories', 'c')
            ->groupBy('b.id, a.id, c.id')
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
