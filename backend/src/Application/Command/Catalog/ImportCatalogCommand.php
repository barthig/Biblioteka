<?php
declare(strict_types=1);
namespace App\Application\Command\Catalog;

class ImportCatalogCommand
{
    public function __construct(
        /** @var array<int, array<string, mixed>> */
        public readonly array $items
    ) {
    }
}
