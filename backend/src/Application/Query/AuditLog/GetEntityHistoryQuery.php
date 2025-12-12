<?php
namespace App\Application\Query\AuditLog;

class GetEntityHistoryQuery
{
    public function __construct(
        public readonly string $entityType,
        public readonly int $entityId
    ) {
    }
}
