<?php
declare(strict_types=1);
namespace App\ApiDoc;

use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class RouteOperationDescriber implements DescriberInterface
{
    public function __construct(private RouterInterface $router)
    {
    }

    public function describe(OA\OpenApi $api)
    {
        foreach ($this->router->getRouteCollection() as $name => $route) {
            if (!$route instanceof Route) {
                continue;
            }

            $path = $route->getPath();
            if (!str_starts_with($path, '/api') || str_starts_with($path, '/api/docs')) {
                continue;
            }

            $methods = $route->getMethods();
            if ($methods === []) {
                $methods = ['GET'];
            }

            $pathItem = Util::getPath($api, $path);

            foreach ($methods as $method) {
                $methodLower = strtolower($method);
                if (!in_array($methodLower, Util::OPERATIONS, true)) {
                    continue;
                }

                if (!Generator::isDefault($pathItem->{$methodLower})) {
                    continue;
                }

                $operation = Util::getOperation($pathItem, $methodLower);
                $this->applyOperationId($operation, (string) $name, $methodLower);
                $this->ensureResponse($operation, $this->defaultResponseCode($method));
                $this->attachPathParameters($operation, $route);
                $this->applySecurityOverrides($operation, $route, $path, $method);
            }
        }
    }

    private function applyOperationId(OA\Operation $operation, string $routeName, string $method): void
    {
        if (Generator::isDefault($operation->operationId)) {
            $operation->operationId = sprintf('%s_%s', $routeName, $method);
        }
    }

    private function ensureResponse(OA\Operation $operation, string $code): void
    {
        $response = Util::getCollectionItem($operation, OA\Response::class, ['response' => $code]);
        if (Generator::isDefault($response->description)) {
            $response->description = $this->responseDescription($code);
        }
    }

    private function attachPathParameters(OA\Operation $operation, Route $route): void
    {
        $pathVariables = $route->compile()->getPathVariables();
        if ($pathVariables === []) {
            return;
        }

        $requirements = $route->getRequirements();
        foreach ($pathVariables as $variable) {
            $param = Util::getOperationParameter($operation, $variable, 'path');
            $param->required = true;

            $type = $this->inferParamType($requirements[$variable] ?? '');
            if (Generator::isDefault($param->schema)) {
                $param->schema = new OA\Schema(['type' => $type]);
            }

            if (Generator::isDefault($param->description)) {
                $param->description = sprintf('Path parameter: %s', $variable);
            }
        }
    }

    private function applySecurityOverrides(OA\Operation $operation, Route $route, string $path, string $method): void
    {
        if ($this->isPublicRoute($route, $path, $method)) {
            $operation->security = [];
        }
    }

    private function isPublicRoute(Route $route, string $path, string $method): bool
    {
        if ($route->getDefault('_public') === true) {
            return true;
        }

        $method = strtoupper($method);
        $publicRoutes = [
            '/api/auth/login' => ['POST'],
            '/api/auth/register' => ['POST'],
            '/api/auth/refresh' => ['POST'],
        ];

        if (isset($publicRoutes[$path]) && in_array($method, $publicRoutes[$path], true)) {
            return true;
        }

        if ($path === '/api/test-login' && $this->isDebugEnv()) {
            return true;
        }

        $publicPatterns = [
            ['pattern' => '#^/api/books$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/filters$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/recommended$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/popular$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/new$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/\\d+$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/\\d+/availability$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/\\d+/ratings$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/collections$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/collections/\\d+$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/announcements$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/announcements/\\d+$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/auth/verify/[A-Za-z0-9]+$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/library/hours$#', 'methods' => ['GET']],
        ];

        foreach ($publicPatterns as $entry) {
            if (preg_match($entry['pattern'], $path) && in_array($method, $entry['methods'], true)) {
                return true;
            }
        }

        return false;
    }

    private function defaultResponseCode(string $method): string
    {
        return match (strtoupper($method)) {
            'POST' => '201',
            'DELETE' => '204',
            default => '200',
        };
    }

    private function responseDescription(string $code): string
    {
        return match ($code) {
            '201' => 'Created',
            '204' => 'No Content',
            '200' => 'OK',
            default => 'Response',
        };
    }

    private function inferParamType(string $requirement): string
    {
        $requirement = trim($requirement);
        if ($requirement === '' || $requirement === '.*') {
            return 'string';
        }

        if (preg_match('#^\\[0-9\\]\\+$#', $requirement) || preg_match('#^\\d\\+$#', $requirement)) {
            return 'integer';
        }

        return 'string';
    }

    private function isDebugEnv(): bool
    {
        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod');
        return in_array($env, ['dev', 'test'], true);
    }
}
