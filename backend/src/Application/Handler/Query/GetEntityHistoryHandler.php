<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\AuditLog\GetEntityHistoryQuery;
use App\Repository\AuditLogRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetEntityHistoryHandler
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository
    ) {
    }

    public function __invoke(GetEntityHistoryQuery $query): array
    {
        return $this->auditLogRepository->findByEntity(
            $query->entityType,
            $query->entityId
        );
    }
}
