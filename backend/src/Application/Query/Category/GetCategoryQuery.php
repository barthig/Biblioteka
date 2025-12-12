<?php
namespace App\Application\Query\Category;

class GetCategoryQuery
{
    public function __construct(
        public readonly int $categoryId
    ) {
    }
}
