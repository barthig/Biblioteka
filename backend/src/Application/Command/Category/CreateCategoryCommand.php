<?php
namespace App\Application\Command\Category;

class CreateCategoryCommand
{
    public function __construct(
        public readonly string $name
    ) {
    }
}
