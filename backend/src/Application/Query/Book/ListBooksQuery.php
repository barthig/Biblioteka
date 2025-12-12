<?php
namespace App\Application\Query\Book;

class ListBooksQuery
{
    public function __construct(
        public readonly ?string $q = null,
        public readonly ?int $authorId = null,
        public readonly ?int $categoryId = null,
        public readonly ?string $publisher = null,
        public readonly ?string $resourceType = null,
        public readonly ?string $signature = null,
        public readonly ?int $yearFrom = null,
        public readonly ?int $yearTo = null,
        public readonly ?string $ageGroup = null,
        public readonly ?string $available = null,
        public readonly int $page = 1,
        public readonly int $limit = 20,
        public readonly ?int $userId = null
    ) {
    }
}
