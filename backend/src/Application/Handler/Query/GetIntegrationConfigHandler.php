<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\IntegrationConfig\GetIntegrationConfigQuery;
use App\Entity\IntegrationConfig;
use App\Repository\IntegrationConfigRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetIntegrationConfigHandler
{
    public function __construct(
        private IntegrationConfigRepository $integrationConfigRepository
    ) {
    }

    public function __invoke(GetIntegrationConfigQuery $query): IntegrationConfig
    {
        $config = $this->integrationConfigRepository->find($query->configId);
        
        if (!$config) {
            throw new NotFoundHttpException('Integration config not found');
        }

        return $config;
    }
}
