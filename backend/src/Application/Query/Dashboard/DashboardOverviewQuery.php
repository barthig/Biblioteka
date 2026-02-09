<?php
declare(strict_types=1);
namespace App\Application\Query\Dashboard;

class DashboardOverviewQuery
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}
