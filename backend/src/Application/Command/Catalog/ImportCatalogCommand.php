<?php
namespace App\Application\Command\Catalog;

class ImportCatalogCommand
{
    public function __construct(
        public readonly array $items
    ) {
    }
}
