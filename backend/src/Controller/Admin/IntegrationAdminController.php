<?php
declare(strict_types=1);
namespace App\Controller\Admin;

use App\Application\Command\IntegrationConfig\CreateIntegrationConfigCommand;
use App\Application\Command\IntegrationConfig\UpdateIntegrationConfigCommand;
use App\Application\Query\IntegrationConfig\GetIntegrationConfigQuery;
use App\Application\Query\IntegrationConfig\ListIntegrationConfigsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Entity\IntegrationConfig;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin/IntegrationAdmin')]
class IntegrationAdminController extends AbstractController
{
    use ExceptionHandlingTrait;
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus
    ) {}

    #[OA\Get(
        path: '/api/admin/integrations',
        summary: 'List integration configurations',
        tags: ['Admin/IntegrationAdmin'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $envelope = $this->queryBus->dispatch(new ListIntegrationConfigsQuery(page: 1, limit: 200));
        $configs = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        $data = array_map(
            static fn(IntegrationConfig $config) => [
                'id' => $config->getId(),
                'name' => $config->getName(),
                'provider' => $config->getProvider(),
                'enabled' => $config->isEnabled(),
                'settings' => $config->getSettings(),
                'lastStatus' => $config->getLastStatus(),
                'lastTestedAt' => $config->getLastTestedAt()?->format(DATE_ATOM),
            ],
            $configs
        );

        return $this->json(['integrations' => $data], 200);
    }

    #[OA\Post(
        path: '/api/admin/integrations',
        summary: 'Create integration configuration',
        tags: ['Admin/IntegrationAdmin'],
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
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $provider = isset($data['provider']) ? trim((string) $data['provider']) : '';
        $settings = isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : [];
        $enabled = isset($data['enabled']) ? (bool) $data['enabled'] : true;

        if ($name === '' || $provider === '') {
            return $this->jsonErrorMessage(400, 'name and provider are required');
        }

        $command = new CreateIntegrationConfigCommand(
            name: $name,
            provider: $provider,
            enabled: $enabled,
            settings: $settings
        );

        $envelope = $this->commandBus->dispatch($command);
        $config = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json([
            'id' => $config->getId(),
            'name' => $config->getName(),
            'provider' => $config->getProvider(),
            'enabled' => $config->isEnabled(),
        ], 201);
    }

    #[OA\Put(
        path: '/api/admin/integrations/{id}',
        summary: 'Update integration configuration',
        tags: ['Admin/IntegrationAdmin'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(int $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $command = new UpdateIntegrationConfigCommand(
            configId: $id,
            name: $data['name'] ?? null,
            provider: $data['provider'] ?? null,
            enabled: array_key_exists('enabled', $data) ? (bool) $data['enabled'] : null,
            settings: isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : null
        );

        $envelope = $this->commandBus->dispatch($command);
        $config = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json([
            'id' => $config->getId(),
            'name' => $config->getName(),
            'provider' => $config->getProvider(),
            'enabled' => $config->isEnabled(),
            'settings' => $config->getSettings(),
        ], 200);
    }

    #[OA\Post(
        path: '/api/admin/integrations/{id}/test',
        summary: 'Test integration connection',
        tags: ['Admin/IntegrationAdmin'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unprocessable entity', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function testConnection(int $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $configEnvelope = $this->queryBus->dispatch(new GetIntegrationConfigQuery(configId: $id));
        /** @var IntegrationConfig $config */
        $config = $configEnvelope->last(HandledStamp::class)?->getResult();

        $settings = $config->getSettings();
        $requiredKeys = ['apiKey', 'endpoint'];
        $missing = array_filter($requiredKeys, static fn($key) => !array_key_exists($key, $settings));

        $status = empty($missing) ? 'ok' : 'misconfigured';
        $testedAt = new \DateTimeImmutable();
        $this->commandBus->dispatch(
            new UpdateIntegrationConfigCommand(
                configId: $id,
                lastStatus: $status,
                lastTestedAt: $testedAt
            )
        );

        return $this->json([
            'status' => $status,
            'missing' => array_values($missing),
            'lastTestedAt' => $testedAt->format(DATE_ATOM),
        ], empty($missing) ? 200 : 422);
    }
}

