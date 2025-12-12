<?php
namespace App\Application\Command\IntegrationConfig;

class CreateIntegrationConfigCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $provider,
        public readonly bool $enabled = true,
        public readonly array $settings = []
    ) {
    }
}
