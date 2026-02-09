<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\IntegrationConfig\CreateIntegrationConfigCommand;
use App\Entity\IntegrationConfig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateIntegrationConfigHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(CreateIntegrationConfigCommand $command): IntegrationConfig
    {
        $config = new IntegrationConfig();
        $config->setName($command->name);
        $config->setProvider($command->provider);
        $config->setEnabled($command->enabled);
        $config->setLastStatus('configured');
        
        if (!empty($command->settings)) {
            $config->setSettings($command->settings);
        }

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        return $config;
    }
}
