<?php
namespace App\Application\Command\Book;

class CreateBookCommand
{
    public function __construct(
        public readonly string $title,
        public readonly int $authorId,
        public readonly string $isbn,
        public readonly string $description,
        public readonly array $categoryIds,
        public readonly ?string $publisher = null,
        public readonly ?int $publicationYear = null,
        public readonly ?string $resourceType = null,
        public readonly ?string $signature = null,
        public readonly ?string $targetAgeGroup = null,
        public readonly int $totalCopies = 1,
        public readonly ?int $availableCopies = null
    ) {
    }
}
