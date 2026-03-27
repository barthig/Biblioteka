<?php
declare(strict_types=1);
namespace App\ApiDoc;

use App\Security\PublicRouteMatcher;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations as OA;
use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class RouteOperationDescriber
{
    public function __construct(
        private RouterInterface $router,
        private PublicRouteMatcher $publicRouteMatcher
    )
    {
    }

    public function describe(OpenApi $api): void
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

                $operation = Util::getOperation($pathItem, $methodLower);

                if (Generator::isDefault($pathItem->{$methodLower})) {
                    $this->applyOperationId($operation, (string) $name, $methodLower);
                    $this->ensureResponse($operation, $this->defaultResponseCode($method));
                }

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

        return $this->publicRouteMatcher->isPublicPath($path, $method);
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
}