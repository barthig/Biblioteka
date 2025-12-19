<?php
namespace App\Application\Command\Collection;

class UpdateCollectionCommand
{
    public function __construct(
        public readonly int $collectionId,
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?bool $featured = null,
        public readonly ?int $displayOrder = null,
        /** @var int[]|null */
        public readonly ?array $bookIds = null
    ) {
    }
}
