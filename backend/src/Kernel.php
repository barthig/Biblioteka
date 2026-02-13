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

    public function boot(): void
    {
        parent::boot();

        // S-02: Reject known placeholder secrets in production
        if ($this->environment === 'prod') {
            $placeholders = ['change_me', 'change_me_secret', 'change_me_api', 'change_me_jwt'];
            $criticalVars = ['APP_SECRET', 'API_SECRET', 'JWT_SECRET'];

            foreach ($criticalVars as $var) {
                $value = $_ENV[$var] ?? $_SERVER[$var] ?? getenv($var) ?: '';
                if (in_array($value, $placeholders, true) || str_starts_with($value, 'change_me')) {
                    throw new \RuntimeException(sprintf(
                        'SECURITY: Environment variable "%s" contains a placeholder value. '
                        . 'Set a real secret before running in production.',
                        $var
                    ));
                }
            }
        }
    }

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
