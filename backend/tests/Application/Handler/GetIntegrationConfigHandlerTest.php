<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\GetIntegrationConfigHandler;
use App\Application\Query\IntegrationConfig\GetIntegrationConfigQuery;
use App\Entity\IntegrationConfig;
use App\Repository\IntegrationConfigRepository;
use PHPUnit\Framework\TestCase;

class GetIntegrationConfigHandlerTest extends TestCase
{
    private IntegrationConfigRepository $integrationConfigRepository;
    private GetIntegrationConfigHandler $handler;

    protected function setUp(): void
    {
        $this->integrationConfigRepository = $this->createMock(IntegrationConfigRepository::class);
        $this->handler = new GetIntegrationConfigHandler($this->integrationConfigRepository);
    }

    public function testGetIntegrationConfigSuccess(): void
    {
        $config = $this->createMock(IntegrationConfig::class);
        $this->integrationConfigRepository->method('find')->with(1)->willReturn($config);

        $query = new GetIntegrationConfigQuery(configId: 1);
        $result = ($this->handler)($query);

        $this->assertSame($config, $result);
    }
}
