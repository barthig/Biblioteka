<?php
namespace App\Application\Command\Category;

class UpdateCategoryCommand
{
    public function __construct(
        public readonly int $categoryId,
        public readonly ?string $name = null
    ) {
    }
}
