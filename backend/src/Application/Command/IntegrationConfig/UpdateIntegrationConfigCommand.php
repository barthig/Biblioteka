<?php
namespace App\Application\Command\IntegrationConfig;

class UpdateIntegrationConfigCommand
{
    public function __construct(
        public readonly int $configId,
        public readonly ?string $name = null,
        public readonly ?bool $enabled = null,
        public readonly ?array $settings = null
    ) {
    }
}
