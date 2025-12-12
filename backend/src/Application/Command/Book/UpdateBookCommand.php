<?php
namespace App\Application\Command\Book;

class UpdateBookCommand
{
    public function __construct(
        public readonly int $bookId,
        public readonly ?string $title = null,
        public readonly ?int $authorId = null,
        public readonly ?string $isbn = null,
        public readonly ?string $description = null,
        public readonly ?array $categoryIds = null,
        public readonly ?string $publisher = null,
        public readonly ?int $publicationYear = null,
        public readonly ?string $resourceType = null,
        public readonly ?string $signature = null,
        public readonly ?string $targetAgeGroup = null
    ) {
    }
}
