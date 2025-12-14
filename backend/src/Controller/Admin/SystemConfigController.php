<?php
namespace App\Controller\Admin;

use App\Application\Command\SystemSetting\CreateSystemSettingCommand;
use App\Application\Command\SystemSetting\UpdateSystemSettingCommand;
use App\Application\Query\SystemSetting\GetSystemSettingByKeyQuery;
use App\Application\Query\SystemSetting\ListSystemSettingsQuery;
use App\Entity\SystemSetting;
use App\Repository\SystemSettingRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class SystemConfigController extends AbstractController
{
    public function __construct(
        private SystemSettingRepository $settingsRepository,
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus
    )
    {
    }

    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
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

    public function create(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $key = $data['key'] ?? null;
        $value = $data['value'] ?? null;
        $type = $data['type'] ?? SystemSetting::TYPE_STRING;

        if (!$key || $value === null) {
            return $this->json(['error' => 'Key and value are required'], 400);
        }

        if ($this->settingsRepository->findOneByKey($key)) {
            return $this->json(['error' => 'Setting already exists'], 409);
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

    public function update(string $key, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
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
