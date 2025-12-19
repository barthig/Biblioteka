<?php
namespace App\Application\Command\IntegrationConfig;

class CreateIntegrationConfigCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $provider,
        public readonly bool $enabled = true,
        /** @var array<string, mixed> */
        public readonly array $settings = []
    ) {
    }
}
