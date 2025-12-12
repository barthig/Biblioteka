<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListIntegrationConfigsHandler;
use App\Application\Query\IntegrationConfig\ListIntegrationConfigsQuery;
use App\Repository\IntegrationConfigRepository;
use PHPUnit\Framework\TestCase;

class ListIntegrationConfigsHandlerTest extends TestCase
{
    private IntegrationConfigRepository $integrationConfigRepository;
    private ListIntegrationConfigsHandler $handler;

    protected function setUp(): void
    {
        $this->integrationConfigRepository = $this->createMock(IntegrationConfigRepository::class);
        $this->handler = new ListIntegrationConfigsHandler($this->integrationConfigRepository);
    }

    public function testListIntegrationConfigsSuccess(): void
    {
        $this->integrationConfigRepository->method('findBy')->willReturn([]);

        $query = new ListIntegrationConfigsQuery(page: 1, limit: 50);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
