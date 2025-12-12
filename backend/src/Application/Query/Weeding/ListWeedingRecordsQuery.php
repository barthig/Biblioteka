<?php
namespace App\Application\Query\Weeding;

class ListWeedingRecordsQuery
{
    public function __construct(public readonly int $limit = 200)
    {
    }
}
