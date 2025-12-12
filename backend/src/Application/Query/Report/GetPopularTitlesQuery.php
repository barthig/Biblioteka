<?php
namespace App\Application\Query\Report;

class GetPopularTitlesQuery
{
    public function __construct(
        public readonly int $limit = 10,
        public readonly int $days = 90
    ) {
    }
}
