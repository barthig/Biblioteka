<?php
namespace App\Controller\Admin;

use App\Entity\IntegrationConfig;
use App\Repository\IntegrationConfigRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class IntegrationAdminController extends AbstractController
{
    public function __construct(private IntegrationConfigRepository $integrations)
    {
    }

    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = array_map(static function (IntegrationConfig $config): array {
            return [
                'id' => $config->getId(),
                'name' => $config->getName(),
                'provider' => $config->getProvider(),
                'enabled' => $config->isEnabled(),
                'settings' => $config->getSettings(),
                'lastStatus' => $config->getLastStatus(),
                'lastTestedAt' => $config->getLastTestedAt()?->format(DATE_ATOM),
            ];
        }, $this->integrations->findBy([], ['name' => 'ASC']));

        return $this->json(['integrations' => $data], 200);
    }

    public function create(Request $request, SecurityService $security, ManagerRegistry $doctrine): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $provider = isset($data['provider']) ? trim((string) $data['provider']) : '';
        $settings = isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : [];
        $enabled = isset($data['enabled']) ? (bool) $data['enabled'] : true;

        if ($name === '' || $provider === '') {
            return $this->json(['error' => 'name and provider are required'], 400);
        }

        $config = (new IntegrationConfig())
            ->setName($name)
            ->setProvider($provider)
            ->setEnabled($enabled)
            ->setSettings($settings)
            ->setLastStatus('configured');

        $em = $doctrine->getManager();
        $em->persist($config);
        $em->flush();

        return $this->json([
            'id' => $config->getId(),
            'name' => $config->getName(),
            'provider' => $config->getProvider(),
            'enabled' => $config->isEnabled(),
        ], 201);
    }

    public function update(int $id, Request $request, SecurityService $security, ManagerRegistry $doctrine): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $config = $this->integrations->find($id);
        if (!$config) {
            return $this->json(['error' => 'Integration not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        if (isset($data['name'])) {
            $config->setName((string) $data['name']);
        }
        if (isset($data['provider'])) {
            $config->setProvider((string) $data['provider']);
        }
        if (isset($data['enabled'])) {
            $config->setEnabled((bool) $data['enabled']);
        }
        if (isset($data['settings']) && is_array($data['settings'])) {
            $config->setSettings($data['settings']);
        }

        $em = $doctrine->getManager();
        $em->persist($config);
        $em->flush();

        return $this->json([
            'id' => $config->getId(),
            'name' => $config->getName(),
            'provider' => $config->getProvider(),
            'enabled' => $config->isEnabled(),
            'settings' => $config->getSettings(),
        ], 200);
    }

    public function testConnection(int $id, Request $request, SecurityService $security, ManagerRegistry $doctrine): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $config = $this->integrations->find($id);
        if (!$config) {
            return $this->json(['error' => 'Integration not found'], 404);
        }

        $settings = $config->getSettings();
        $requiredKeys = ['apiKey', 'endpoint'];
        $missing = array_filter($requiredKeys, static fn($key) => !array_key_exists($key, $settings));

        $status = empty($missing) ? 'ok' : 'misconfigured';
        $config->setLastStatus($status)->setLastTestedAt(new \DateTimeImmutable());

        $em = $doctrine->getManager();
        $em->persist($config);
        $em->flush();

        return $this->json([
            'status' => $status,
            'missing' => array_values($missing),
            'lastTestedAt' => $config->getLastTestedAt()?->format(DATE_ATOM),
        ], empty($missing) ? 200 : 422);
    }
}
