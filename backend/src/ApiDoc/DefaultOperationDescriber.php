<?php
declare(strict_types=1);
namespace App\ApiDoc;

use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\JsonContent;
use OpenApi\Annotations\MediaType;
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
            if (Generator::isDefault($pathItem->path)) {
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
                $this->applyResponseSchemas($operation, $path);
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

    private function applyResponseSchemas(Operation $operation, string $path): void
    {
        if (Generator::isDefault($operation->responses)) {
            return;
        }

        foreach ($operation->responses as $response) {
            if (Generator::isDefault($response->response)) {
                continue;
            }

            $code = (string) $response->response;
            if ($code === '204') {
                continue;
            }

            if ($this->hasJsonContent($response)) {
                continue;
            }

            $schemaRef = $this->schemaForResponse($operation, $path, $code);

            $schemaObject = (object) ['$ref' => $schemaRef];
            $response->content = [
                new MediaType([
                    'mediaType' => 'application/json',
                    'schema' => $schemaObject,
                ]),
            ];
        }
    }

    private function hasJsonContent(object $response): bool
    {
        if (!Generator::isDefault($response->content)) {
            if ($response->content instanceof JsonContent) {
                return true;
            }
            if ($response->content instanceof MediaType && $response->content->mediaType === 'application/json') {
                return true;
            }
            if (is_array($response->content)) {
                foreach ($response->content as $content) {
                    if ($content instanceof JsonContent) {
                        return true;
                    }
                    if ($content instanceof MediaType && $content->mediaType === 'application/json') {
                        return true;
                    }
                }
            }
        }

        if (!Generator::isDefault($response->_unmerged)) {
            foreach ($response->_unmerged as $content) {
                if ($content instanceof JsonContent) {
                    return true;
                }
                if ($content instanceof MediaType && $content->mediaType === 'application/json') {
                    return true;
                }
            }
        }

        return false;
    }

    private function schemaForResponse(Operation $operation, string $path, string $code): string
    {
        if ($code[0] === '4' || $code[0] === '5') {
            if ($code === '400' || $code === '422') {
                return '#/components/schemas/ValidationErrorResponse';
            }

            return '#/components/schemas/ErrorResponse';
        }

        if ($code[0] !== '2') {
            return '#/components/schemas/MessageResponse';
        }

        $method = Generator::isDefault($operation->method) ? '' : strtoupper((string) $operation->method);
        if ($method === 'DELETE') {
            return '#/components/schemas/MessageResponse';
        }

        $hasPathParam = str_contains($path, '{');
        if ($method === 'GET' && !$hasPathParam) {
            return '#/components/schemas/ListResponse';
        }

        return '#/components/schemas/ItemResponse';
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
