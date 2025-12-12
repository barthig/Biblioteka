<?php
namespace App\Application\Query\Category;

class ListCategoriesQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 50
    ) {
    }
}
