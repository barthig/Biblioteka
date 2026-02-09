<?php
declare(strict_types=1);
namespace App\Application\Command\IntegrationConfig;

class DeleteIntegrationConfigCommand
{
    public function __construct(
        public readonly int $configId
    ) {
    }
}
