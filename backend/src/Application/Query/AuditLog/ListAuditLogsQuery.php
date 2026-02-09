<?php
declare(strict_types=1);
namespace App\Application\Query\AuditLog;

class ListAuditLogsQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 50,
        public readonly array $filters = []
    ) {
    }
}
