<?php
namespace App\Application\Query\IntegrationConfig;

class GetIntegrationConfigQuery
{
    public function __construct(
        public readonly int $configId
    ) {
    }
}
