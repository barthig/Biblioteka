<?php
namespace App\Application\Query\Acquisition;

class ListBudgetsQuery
{
    public function __construct(public readonly ?string $year = null)
    {
    }
}
