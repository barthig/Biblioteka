<?php
namespace App\Controller\Admin;

use App\Application\Command\SystemSetting\CreateSystemSettingCommand;
use App\Application\Command\SystemSetting\UpdateSystemSettingCommand;
use App\Application\Query\SystemSetting\GetSystemSettingByKeyQuery;
use App\Application\Query\SystemSetting\ListSystemSettingsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Entity\SystemSetting;
use App\Repository\SystemSettingRepository;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin/SystemConfig')]
class SystemConfigController extends AbstractController
{
    use ExceptionHandlingTrait;
    public function __construct(
        private SystemSettingRepository $settingsRepository,
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus
    )
    {
    }

    #[OA\Get(
        path: '/api/admin/system/settings',
        summary: 'List system settings',
        tags: ['Admin'],
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

        $envelope = $this->queryBus->dispatch(new ListSystemSettingsQuery(page: 1, limit: 500));
        $settings = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        $data = array_map(static function (SystemSetting $setting): array {
            return [
                'key' => $setting->getKey(),
                'value' => $setting->getValue(),
                'type' => $setting->getValueType(),
                'description' => $setting->getDescription(),
                'updatedAt' => $setting->getUpdatedAt()->format(DATE_ATOM),
            ];
        }, $settings);

        return $this->json(['settings' => $data], 200, [], ['json_encode_options' => JSON_PRESERVE_ZERO_FRACTION]);
    }

    #[OA\Post(
        path: '/api/admin/system/settings',
        summary: 'Create system setting',
        tags: ['Admin'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['key', 'value'],
                properties: [
                    new OA\Property(property: 'key', type: 'string'),
                    new OA\Property(property: 'value', type: 'string'),
                    new OA\Property(property: 'type', type: 'string', nullable: true),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Already exists', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $key = $data['key'] ?? null;
        $value = $data['value'] ?? null;
        $type = $data['type'] ?? SystemSetting::TYPE_STRING;

        if (!$key || $value === null) {
            return $this->jsonErrorMessage(400, 'Key and value are required');
        }

        if ($this->settingsRepository->findOneByKey($key)) {
            return $this->jsonErrorMessage(409, 'Setting already exists');
        }

        $command = new CreateSystemSettingCommand(
            key: $key,
            value: $value,
            valueType: $type,
            description: $data['description'] ?? null
        );

        $envelope = $this->commandBus->dispatch($command);
        $setting = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json([
            'key' => $setting->getKey(),
            'value' => $setting->getValue(),
            'type' => $setting->getValueType(),
            'description' => $setting->getDescription(),
        ], 201, [], ['json_encode_options' => JSON_PRESERVE_ZERO_FRACTION]);
    }

    #[OA\Put(
        path: '/api/admin/system/settings/{key}',
        summary: 'Update system setting',
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'key', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'value', type: 'string', nullable: true),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'type', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(string $key, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $settingEnvelope = $this->queryBus->dispatch(new GetSystemSettingByKeyQuery($key));
        $setting = $settingEnvelope->last(HandledStamp::class)?->getResult();

        $data = json_decode($request->getContent(), true) ?: [];
        $command = new UpdateSystemSettingCommand(
            settingId: $setting->getId(),
            value: array_key_exists('value', $data) ? $data['value'] : null,
            description: array_key_exists('description', $data) ? $data['description'] : null,
            valueType: array_key_exists('type', $data) ? (string) $data['type'] : null
        );

        $envelope = $this->commandBus->dispatch($command);
        $updated = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json([
            'key' => $updated->getKey(),
            'value' => $updated->getValue(),
            'type' => $updated->getValueType(),
            'description' => $updated->getDescription(),
        ], 200, [], ['json_encode_options' => JSON_PRESERVE_ZERO_FRACTION]);
    }
}
