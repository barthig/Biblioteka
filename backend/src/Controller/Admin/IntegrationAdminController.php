<?php
namespace App\Controller\Admin;

use App\Application\Command\IntegrationConfig\CreateIntegrationConfigCommand;
use App\Application\Command\IntegrationConfig\UpdateIntegrationConfigCommand;
use App\Application\Query\IntegrationConfig\GetIntegrationConfigQuery;
use App\Application\Query\IntegrationConfig\ListIntegrationConfigsQuery;
use App\Entity\IntegrationConfig;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class IntegrationAdminController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus
    ) {}

    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
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

    public function create(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $provider = isset($data['provider']) ? trim((string) $data['provider']) : '';
        $settings = isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : [];
        $enabled = isset($data['enabled']) ? (bool) $data['enabled'] : true;

        if ($name === '' || $provider === '') {
            return $this->json(['message' => 'name and provider are required'], 400);
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

    public function update(int $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
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

    public function testConnection(int $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
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
