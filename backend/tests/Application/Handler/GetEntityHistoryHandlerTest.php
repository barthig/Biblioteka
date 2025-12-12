<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\GetEntityHistoryHandler;
use App\Application\Query\AuditLog\GetEntityHistoryQuery;
use App\Repository\AuditLogRepository;
use PHPUnit\Framework\TestCase;

class GetEntityHistoryHandlerTest extends TestCase
{
    private AuditLogRepository $auditLogRepository;
    private GetEntityHistoryHandler $handler;

    protected function setUp(): void
    {
        $this->auditLogRepository = $this->createMock(AuditLogRepository::class);
        $this->handler = new GetEntityHistoryHandler($this->auditLogRepository);
    }

    public function testGetEntityHistorySuccess(): void
    {
        $this->auditLogRepository->method('findByEntity')
            ->with('Book', 1)
            ->willReturn([]);

        $query = new GetEntityHistoryQuery(entityType: 'Book', entityId: 1);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
