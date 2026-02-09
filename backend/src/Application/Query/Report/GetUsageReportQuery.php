<?php
declare(strict_types=1);
namespace App\Application\Query\Report;

class GetUsageReportQuery
{
    public function __construct(
        public readonly ?string $from = null,
        public readonly ?string $to = null
    ) {
    }
}
