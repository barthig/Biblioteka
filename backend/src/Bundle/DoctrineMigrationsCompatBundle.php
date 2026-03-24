<?php

declare(strict_types=1);

namespace App\Bundle;

use Doctrine\Bundle\MigrationsBundle\DependencyInjection\CompilerPass\ConfigureDependencyFactoryPass;
use Doctrine\Bundle\MigrationsBundle\DependencyInjection\DoctrineMigrationsExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class DoctrineMigrationsCompatBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ConfigureDependencyFactoryPass());

        $registerMigrationsPass = 'Doctrine\\Bundle\\MigrationsBundle\\DependencyInjection\\CompilerPass\\RegisterMigrationsPass';
        if (class_exists($registerMigrationsPass)) {
            $container->addCompilerPass(new $registerMigrationsPass());
        }
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DoctrineMigrationsExtension();
    }
}