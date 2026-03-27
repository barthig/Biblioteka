<?php

declare(strict_types=1);

namespace Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (!class_exists(WellKnownSchemaFilterPass::class, false)) {
    $vendorClass = __DIR__ . '/../vendor/doctrine/doctrine-bundle/src/DependencyInjection/Compiler/WellKnownSchemaFilterPass.php';

    if (is_file($vendorClass)) {
        require_once $vendorClass;
    }
}

if (!class_exists(WellKnownSchemaFilterPass::class, false)) {
    /**
     * Compatibility shim for environments where DoctrineBundle ships the class
     * but Composer autoload fails to resolve it during early kernel boot.
     */
    final class WellKnownSchemaFilterPass implements CompilerPassInterface
    {
        public function process(ContainerBuilder $container): void
        {
        }
    }
}