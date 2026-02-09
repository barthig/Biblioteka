<?php
namespace App\Controller;

use App\Application\Command\SystemSetting\CreateSystemSettingCommand;
use App\Application\Command\SystemSetting\DeleteSystemSettingCommand;
use App\Application\Command\SystemSetting\UpdateSystemSettingCommand;
use App\Application\Query\SystemSetting\GetSystemSettingQuery;
use App\Application\Query\SystemSetting\ListSystemSettingsQuery;
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

#[OA\Tag(name: 'SystemSetting')]
class SystemSettingController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus
    ) {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/system-settings',
        summary: 'List system settings',
        tags: ['SystemSetting'],
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

        $query = new ListSystemSettingsQuery(page: $page, limit: $limit);
        $envelope = $this->queryBus->dispatch($query);
        $settings = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json($settings);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/system-settings/{id}',
        summary: 'Get system setting',
        tags: ['SystemSetting'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function get(int $id): JsonResponse
    {
        $query = new GetSystemSettingQuery(settingId: $id);
        $envelope = $this->queryBus->dispatch($query);
        $setting = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($setting);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(
        path: '/api/system-settings',
        summary: 'Create system setting',
        tags: ['SystemSetting'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['key', 'value'],
                properties: [
                    new OA\Property(property: 'key', type: 'string'),
                    new OA\Property(property: 'value', type: 'string'),
                    new OA\Property(property: 'valueType', type: 'string', nullable: true),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
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

        $command = new CreateSystemSettingCommand(
            key: $data['key'] ?? '',
            value: $data['value'] ?? '',
            valueType: $data['valueType'] ?? 'string',
            description: $data['description'] ?? null
        );

        $envelope = $this->commandBus->dispatch($command);
        $setting = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($setting, Response::HTTP_CREATED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        path: '/api/system-settings/{id}',
        summary: 'Update system setting',
        tags: ['SystemSetting'],
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

        $command = new UpdateSystemSettingCommand(
            settingId: $id,
            value: $data['value'] ?? null,
            description: $data['description'] ?? null
        );

        $envelope = $this->commandBus->dispatch($command);
        $setting = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($setting);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/system-settings/{id}',
        summary: 'Delete system setting',
        tags: ['SystemSetting'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $command = new DeleteSystemSettingCommand(settingId: $id);
        $this->commandBus->dispatch($command);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
