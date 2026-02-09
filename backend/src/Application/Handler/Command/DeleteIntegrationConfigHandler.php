<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\IntegrationConfig\DeleteIntegrationConfigCommand;
use App\Repository\IntegrationConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class DeleteIntegrationConfigHandler
{
    public function __construct(
        private IntegrationConfigRepository $integrationConfigRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(DeleteIntegrationConfigCommand $command): void
    {
        $config = $this->integrationConfigRepository->find($command->configId);
        
        if (!$config) {
            throw new NotFoundHttpException('Integration config not found');
        }

        $this->entityManager->remove($config);
        $this->entityManager->flush();
    }
}
