<?php
declare(strict_types=1);
namespace App\Application\Query\Category;

class GetCategoryQuery
{
    public function __construct(
        public readonly int $categoryId
    ) {
    }
}
