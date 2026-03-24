<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (!class_exists(RegisterMigrationsPass::class, false)) {
    final class RegisterMigrationsPass implements CompilerPassInterface
    {
        public function process(ContainerBuilder $container): void
        {
            // Compatibility fallback for package distributions missing this no-op compiler pass.
        }
    }
}