<?php
declare(strict_types=1);
namespace App\Application\Query\Acquisition;

class GetBudgetSummaryQuery
{
    public function __construct(public readonly int $id)
    {
    }
}
