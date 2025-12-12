<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListAuditLogsHandler;
use App\Application\Query\AuditLog\ListAuditLogsQuery;
use App\Repository\AuditLogRepository;
use PHPUnit\Framework\TestCase;

class ListAuditLogsHandlerTest extends TestCase
{
    private AuditLogRepository $auditLogRepository;
    private ListAuditLogsHandler $handler;

    protected function setUp(): void
    {
        $this->auditLogRepository = $this->createMock(AuditLogRepository::class);
        $this->handler = new ListAuditLogsHandler($this->auditLogRepository);
    }

    public function testListAuditLogsSuccess(): void
    {
        $this->auditLogRepository->method('findBy')->willReturn([]);

        $query = new ListAuditLogsQuery(page: 1, limit: 50);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
