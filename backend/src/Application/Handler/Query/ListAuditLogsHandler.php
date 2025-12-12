<?php
namespace App\Application\Handler\Query;

use App\Application\Query\AuditLog\ListAuditLogsQuery;
use App\Repository\AuditLogRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListAuditLogsHandler
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository
    ) {
    }

    public function __invoke(ListAuditLogsQuery $query): array
    {
        return $this->auditLogRepository->findWithPagination(
            $query->page,
            $query->limit,
            $query->filters
        );
    }
}
