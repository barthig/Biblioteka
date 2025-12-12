<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\IntegrationConfig\UpdateIntegrationConfigCommand;
use App\Application\Handler\Command\UpdateIntegrationConfigHandler;
use App\Entity\IntegrationConfig;
use App\Repository\IntegrationConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateIntegrationConfigHandlerTest extends TestCase
{
    private IntegrationConfigRepository $integrationConfigRepository;
    private EntityManagerInterface $entityManager;
    private UpdateIntegrationConfigHandler $handler;

    protected function setUp(): void
    {
        $this->integrationConfigRepository = $this->createMock(IntegrationConfigRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new UpdateIntegrationConfigHandler($this->integrationConfigRepository, $this->entityManager);
    }

    public function testUpdateIntegrationConfigSuccess(): void
    {
        $config = $this->createMock(IntegrationConfig::class);
        $config->expects($this->once())->method('setName')->with('Updated Name');
        
        $this->integrationConfigRepository->method('find')->with(1)->willReturn($config);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UpdateIntegrationConfigCommand(configId: 1, name: 'Updated Name');
        $result = ($this->handler)($command);

        $this->assertSame($config, $result);
    }
}
