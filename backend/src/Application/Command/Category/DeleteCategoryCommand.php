<?php
namespace App\Application\Command\Category;

class DeleteCategoryCommand
{
    public function __construct(
        public readonly int $categoryId
    ) {
    }
}
