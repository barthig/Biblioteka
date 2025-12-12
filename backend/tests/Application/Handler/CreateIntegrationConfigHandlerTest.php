<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\IntegrationConfig\CreateIntegrationConfigCommand;
use App\Application\Handler\Command\CreateIntegrationConfigHandler;
use App\Entity\IntegrationConfig;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateIntegrationConfigHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CreateIntegrationConfigHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new CreateIntegrationConfigHandler($this->entityManager);
    }

    public function testCreateIntegrationConfigSuccess(): void
    {
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateIntegrationConfigCommand(
            name: 'Test Integration',
            provider: 'test_provider',
            enabled: true,
            settings: ['key' => 'value']
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(IntegrationConfig::class, $result);
    }
}
