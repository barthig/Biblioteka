<?php
namespace App\Controller;

use App\Application\Command\SystemSetting\CreateSystemSettingCommand;
use App\Application\Command\SystemSetting\DeleteSystemSettingCommand;
use App\Application\Command\SystemSetting\UpdateSystemSettingCommand;
use App\Application\Query\SystemSetting\GetSystemSettingQuery;
use App\Application\Query\SystemSetting\ListSystemSettingsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/system-settings')]
class SystemSettingController extends AbstractController
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

        $query = new ListSystemSettingsQuery(page: $page, limit: $limit);
        $envelope = $this->messageBus->dispatch($query);
        $settings = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json($settings);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function get(int $id): JsonResponse
    {
        $query = new GetSystemSettingQuery(settingId: $id);
        $envelope = $this->messageBus->dispatch($query);
        $setting = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($setting);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $command = new CreateSystemSettingCommand(
            key: $data['key'] ?? '',
            value: $data['value'] ?? '',
            valueType: $data['valueType'] ?? 'string',
            description: $data['description'] ?? null
        );

        $envelope = $this->messageBus->dispatch($command);
        $setting = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($setting, Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $command = new UpdateSystemSettingCommand(
            settingId: $id,
            value: $data['value'] ?? null,
            description: $data['description'] ?? null
        );

        $envelope = $this->messageBus->dispatch($command);
        $setting = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($setting);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $command = new DeleteSystemSettingCommand(settingId: $id);
        $this->messageBus->dispatch($command);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
