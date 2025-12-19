<?php
namespace App\ApiDoc;

use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Operation;
use OpenApi\Generator;

class DefaultOperationDescriber implements DescriberInterface
{
    public function describe(OpenApi $api)
    {
        if (Generator::isDefault($api->paths)) {
            return;
        }

        foreach ($api->paths as $pathItem) {
            if (!isset($pathItem->path) || Generator::isDefault($pathItem->path)) {
                continue;
            }

            $path = (string) $pathItem->path;
            $tagName = $this->guessTagName($path);
            $tag = Util::getTag($api, $tagName);
            if (Generator::isDefault($tag->description)) {
                $tag->description = sprintf('Operacje dla zasobu %s.', $tagName);
            }

            foreach ($pathItem->operations() as $operation) {
                $this->applyDefaults($operation, $tagName, $path);
            }
        }
    }

    private function applyDefaults(Operation $operation, string $tagName, string $path): void
    {
        if (Generator::isDefault($operation->tags) || $operation->tags === []) {
            $operation->tags = [$tagName];
        }

        if (Generator::isDefault($operation->summary) || trim((string) $operation->summary) === '') {
            $method = Generator::isDefault($operation->method) ? 'METHOD' : strtoupper((string) $operation->method);
            $operation->summary = sprintf('%s %s', $method, $path);
        }
    }

    private function guessTagName(string $path): string
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        if ($segments === []) {
            return 'Api';
        }

        if ($segments[0] === 'api') {
            array_shift($segments);
        }

        if ($segments === []) {
            return 'Api';
        }

        if ($segments[0] === 'admin' && isset($segments[1])) {
            return $this->titleCase($segments[1]);
        }

        return $this->titleCase($segments[0]);
    }

    private function titleCase(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);

        return str_replace(' ', '', ucwords($value));
    }
}
