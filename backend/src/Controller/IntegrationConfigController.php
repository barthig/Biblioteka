<?php
declare(strict_types=1);
namespace App\Controller;

use App\Application\Command\IntegrationConfig\CreateIntegrationConfigCommand;
use App\Application\Command\IntegrationConfig\DeleteIntegrationConfigCommand;
use App\Application\Command\IntegrationConfig\UpdateIntegrationConfigCommand;
use App\Application\Query\IntegrationConfig\GetIntegrationConfigQuery;
use App\Application\Query\IntegrationConfig\ListIntegrationConfigsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'IntegrationConfig')]
class IntegrationConfigController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus
    ) {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/integration-configs',
        summary: 'List integration configurations',
        tags: ['IntegrationConfig'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 50)));

        $query = new ListIntegrationConfigsQuery(page: $page, limit: $limit);
        $envelope = $this->queryBus->dispatch($query);
        $configs = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json($configs);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/integration-configs/{id}',
        summary: 'Get integration configuration',
        tags: ['IntegrationConfig'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function get(int $id): JsonResponse
    {
        $query = new GetIntegrationConfigQuery(configId: $id);
        $envelope = $this->queryBus->dispatch($query);
        $config = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($config);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(
        path: '/api/integration-configs',
        summary: 'Create integration configuration',
        tags: ['IntegrationConfig'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'provider'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'provider', type: 'string'),
                    new OA\Property(property: 'enabled', type: 'boolean', nullable: true),
                    new OA\Property(property: 'settings', type: 'object', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $command = new CreateIntegrationConfigCommand(
            name: $data['name'] ?? '',
            provider: $data['provider'] ?? '',
            enabled: $data['enabled'] ?? true,
            settings: $data['settings'] ?? []
        );

        $envelope = $this->commandBus->dispatch($command);
        $config = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($config, Response::HTTP_CREATED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        path: '/api/integration-configs/{id}',
        summary: 'Update integration configuration',
        tags: ['IntegrationConfig'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $command = new UpdateIntegrationConfigCommand(
            configId: $id,
            name: $data['name'] ?? null,
            enabled: $data['enabled'] ?? null,
            settings: $data['settings'] ?? null
        );

        $envelope = $this->commandBus->dispatch($command);
        $config = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($config);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/integration-configs/{id}',
        summary: 'Delete integration configuration',
        tags: ['IntegrationConfig'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $command = new DeleteIntegrationConfigCommand(configId: $id);
        $this->commandBus->dispatch($command);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
