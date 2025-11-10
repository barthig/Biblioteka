<?php
namespace App\Controller\Admin;

use App\Entity\SystemSetting;
use App\Repository\SystemSettingRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SystemConfigController extends AbstractController
{
    public function __construct(private SystemSettingRepository $settingsRepository)
    {
    }

    public function list(Request $request, SecurityService $security, ManagerRegistry $doctrine): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $settings = $this->settingsRepository->findBy([], ['settingKey' => 'ASC']);
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

    public function create(Request $request, SecurityService $security, ManagerRegistry $doctrine): JsonResponse
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

        $setting = new SystemSetting();
        $setting->setKey($key)
            ->setValueType($type)
            ->setDescription($data['description'] ?? null)
            ->setValueFromMixed($value);

        $em = $doctrine->getManager();
        $em->persist($setting);
        $em->flush();

        return $this->json([
            'key' => $setting->getKey(),
            'value' => $setting->getValue(),
            'type' => $setting->getValueType(),
            'description' => $setting->getDescription(),
        ], 201, [], ['json_encode_options' => JSON_PRESERVE_ZERO_FRACTION]);
    }

    public function update(string $key, Request $request, SecurityService $security, ManagerRegistry $doctrine): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $setting = $this->settingsRepository->findOneByKey($key);
        if (!$setting) {
            return $this->json(['error' => 'Setting not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        if (array_key_exists('type', $data)) {
            $setting->setValueType((string) $data['type']);
        }
        if (array_key_exists('value', $data)) {
            $setting->setValueFromMixed($data['value']);
        }
        if (array_key_exists('description', $data)) {
            $setting->setDescription($data['description']);
        }

        $em = $doctrine->getManager();
        $em->persist($setting);
        $em->flush();

        return $this->json([
            'key' => $setting->getKey(),
            'value' => $setting->getValue(),
            'type' => $setting->getValueType(),
            'description' => $setting->getDescription(),
        ], 200, [], ['json_encode_options' => JSON_PRESERVE_ZERO_FRACTION]);
    }
}
