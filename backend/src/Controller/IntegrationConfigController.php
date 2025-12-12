<?php
namespace App\Controller;

use App\Application\Command\IntegrationConfig\CreateIntegrationConfigCommand;
use App\Application\Command\IntegrationConfig\DeleteIntegrationConfigCommand;
use App\Application\Command\IntegrationConfig\UpdateIntegrationConfigCommand;
use App\Application\Query\IntegrationConfig\GetIntegrationConfigQuery;
use App\Application\Query\IntegrationConfig\ListIntegrationConfigsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/integration-configs')]
class IntegrationConfigController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 50)));

        $query = new ListIntegrationConfigsQuery(page: $page, limit: $limit);
        $envelope = $this->messageBus->dispatch($query);
        $configs = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json($configs);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function get(int $id): JsonResponse
    {
        $query = new GetIntegrationConfigQuery(configId: $id);
        $envelope = $this->messageBus->dispatch($query);
        $config = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($config);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $command = new CreateIntegrationConfigCommand(
            name: $data['name'] ?? '',
            provider: $data['provider'] ?? '',
            enabled: $data['enabled'] ?? true,
            settings: $data['settings'] ?? []
        );

        $envelope = $this->messageBus->dispatch($command);
        $config = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($config, Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $command = new UpdateIntegrationConfigCommand(
            configId: $id,
            name: $data['name'] ?? null,
            enabled: $data['enabled'] ?? null,
            settings: $data['settings'] ?? null
        );

        $envelope = $this->messageBus->dispatch($command);
        $config = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($config);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $command = new DeleteIntegrationConfigCommand(configId: $id);
        $this->messageBus->dispatch($command);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
