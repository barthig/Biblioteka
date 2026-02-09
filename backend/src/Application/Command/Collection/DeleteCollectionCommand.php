<?php
declare(strict_types=1);
namespace App\Application\Command\Collection;

class DeleteCollectionCommand
{
    public function __construct(
        public readonly int $collectionId
    ) {
    }
}
