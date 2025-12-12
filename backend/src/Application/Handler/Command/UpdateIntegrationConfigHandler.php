<?php
namespace App\Application\Handler\Command;

use App\Application\Command\IntegrationConfig\UpdateIntegrationConfigCommand;
use App\Entity\IntegrationConfig;
use App\Repository\IntegrationConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateIntegrationConfigHandler
{
    public function __construct(
        private IntegrationConfigRepository $integrationConfigRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(UpdateIntegrationConfigCommand $command): IntegrationConfig
    {
        $config = $this->integrationConfigRepository->find($command->configId);
        
        if (!$config) {
            throw new NotFoundHttpException('Integration config not found');
        }

        if ($command->name !== null) {
            $config->setName($command->name);
        }
        
        if ($command->enabled !== null) {
            $config->setEnabled($command->enabled);
        }
        
        if ($command->settings !== null) {
            $config->setSettings($command->settings);
        }

        $this->entityManager->flush();

        return $config;
    }
}
