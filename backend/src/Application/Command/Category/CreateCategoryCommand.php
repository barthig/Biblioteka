<?php
declare(strict_types=1);
namespace App\Application\Command\Category;

class CreateCategoryCommand
{
    public function __construct(
        public readonly string $name
    ) {
    }
}
