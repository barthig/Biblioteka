<?php
namespace App\Controller;

use App\Application\Command\StaffRole\CreateStaffRoleCommand;
use App\Application\Command\StaffRole\DeleteStaffRoleCommand;
use App\Application\Command\StaffRole\UpdateStaffRoleCommand;
use App\Application\Query\StaffRole\GetStaffRoleQuery;
use App\Application\Query\StaffRole\ListStaffRolesQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/staff-roles')]
class StaffRoleController extends AbstractController
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

        $query = new ListStaffRolesQuery(page: $page, limit: $limit);
        $envelope = $this->messageBus->dispatch($query);
        $roles = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json($roles);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function get(int $id): JsonResponse
    {
        $query = new GetStaffRoleQuery(roleId: $id);
        $envelope = $this->messageBus->dispatch($query);
        $role = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($role);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $command = new CreateStaffRoleCommand(
            name: $data['name'] ?? '',
            roleKey: $data['roleKey'] ?? '',
            modules: $data['modules'] ?? [],
            description: $data['description'] ?? null
        );

        $envelope = $this->messageBus->dispatch($command);
        $role = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($role, Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $command = new UpdateStaffRoleCommand(
            roleId: $id,
            name: $data['name'] ?? null,
            modules: $data['modules'] ?? null,
            description: $data['description'] ?? null
        );

        $envelope = $this->messageBus->dispatch($command);
        $role = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($role);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $command = new DeleteStaffRoleCommand(roleId: $id);
        $this->messageBus->dispatch($command);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
