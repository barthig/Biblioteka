<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\IntegrationConfig\DeleteIntegrationConfigCommand;
use App\Application\Handler\Command\DeleteIntegrationConfigHandler;
use App\Entity\IntegrationConfig;
use App\Repository\IntegrationConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteIntegrationConfigHandlerTest extends TestCase
{
    private IntegrationConfigRepository $integrationConfigRepository;
    private EntityManagerInterface $entityManager;
    private DeleteIntegrationConfigHandler $handler;

    protected function setUp(): void
    {
        $this->integrationConfigRepository = $this->createMock(IntegrationConfigRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new DeleteIntegrationConfigHandler($this->integrationConfigRepository, $this->entityManager);
    }

    public function testDeleteIntegrationConfigSuccess(): void
    {
        $config = $this->createMock(IntegrationConfig::class);
        $this->integrationConfigRepository->method('find')->with(1)->willReturn($config);
        $this->entityManager->expects($this->once())->method('remove')->with($config);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new DeleteIntegrationConfigCommand(configId: 1);
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
