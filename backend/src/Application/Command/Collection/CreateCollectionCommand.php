<?php
declare(strict_types=1);
namespace App\Application\Command\Collection;

class CreateCollectionCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly bool $featured = false,
        public readonly int $displayOrder = 0,
        /** @var int[] */
        public readonly array $bookIds = []
    ) {
    }
}
