<?php
namespace App\Application\Query\Acquisition;

class GetBudgetSummaryQuery
{
    public function __construct(public readonly int $id)
    {
    }
}
