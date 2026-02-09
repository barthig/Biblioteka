<?php
declare(strict_types=1);
namespace App\Application\Query\IntegrationConfig;

class GetIntegrationConfigQuery
{
    public function __construct(
        public readonly int $configId
    ) {
    }
}
