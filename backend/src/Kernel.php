<?php
declare(strict_types=1);
namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $confDir = dirname(__DIR__) . '/config';

    $container->import($confDir . '/{packages}/*.yaml');
    $container->import($confDir . '/{packages}/' . $this->environment . '/*.yaml', null, true);

    $container->import($confDir . '/services.yaml');
    $container->import($confDir . '/services_' . $this->environment . '.yaml', null, true);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $confDir = dirname(__DIR__) . '/config';

    $routes->import($confDir . '/{routes}/*.yaml');
    $routes->import($confDir . '/{routes}/' . $this->environment . '/*.yaml', null, true);
    $routes->import($confDir . '/routes.yaml');
    }
}
