<?php
declare(strict_types=1);

namespace App\Application\Query\Recommendation;

class FindSimilarBooksQuery
{
    public function __construct(
        public readonly array $vector,
        public readonly int $limit = 5
    ) {
    }
}
