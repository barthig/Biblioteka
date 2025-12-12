<?php
namespace App\Application\Command\IntegrationConfig;

class DeleteIntegrationConfigCommand
{
    public function __construct(
        public readonly int $configId
    ) {
    }
}
