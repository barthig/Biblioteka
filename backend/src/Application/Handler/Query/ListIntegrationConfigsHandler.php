<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\IntegrationConfig\ListIntegrationConfigsQuery;
use App\Repository\IntegrationConfigRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class ListIntegrationConfigsHandler
{
    public function __construct(
        private IntegrationConfigRepository $integrationConfigRepository
    ) {
    }

    public function __invoke(ListIntegrationConfigsQuery $query): array
    {
        $offset = ($query->page - 1) * $query->limit;
        
        return $this->integrationConfigRepository->findBy(
            [],
            ['name' => 'ASC'],
            $query->limit,
            $offset
        );
    }
}
