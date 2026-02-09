<?php
namespace App\Controller;

use App\Application\Command\StaffRole\CreateStaffRoleCommand;
use App\Application\Command\StaffRole\DeleteStaffRoleCommand;
use App\Application\Command\StaffRole\UpdateStaffRoleCommand;
use App\Application\Query\StaffRole\GetStaffRoleQuery;
use App\Application\Query\StaffRole\ListStaffRolesQuery;
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

#[OA\Tag(name: 'StaffRole')]
class StaffRoleController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/staff-roles',
        summary: 'List staff roles',
        tags: ['StaffRoles'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/StaffRole'))),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 50)));

        $query = new ListStaffRolesQuery(page: $page, limit: $limit);
        $envelope = $this->messageBus->dispatch($query);
        $roles = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json($roles);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/staff-roles/{id}',
        summary: 'Get staff role',
        tags: ['StaffRoles'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/StaffRole')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function get(int $id): JsonResponse
    {
        $query = new GetStaffRoleQuery(roleId: $id);
        $envelope = $this->messageBus->dispatch($query);
        $role = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($role);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(
        path: '/api/staff-roles',
        summary: 'Create staff role',
        tags: ['StaffRoles'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'roleKey'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'roleKey', type: 'string'),
                    new OA\Property(property: 'modules', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/StaffRole')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Conflict', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        path: '/api/staff-roles/{id}',
        summary: 'Update staff role',
        tags: ['StaffRoles'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/StaffRole')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/staff-roles/{id}',
        summary: 'Delete staff role',
        tags: ['StaffRoles'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $command = new DeleteStaffRoleCommand(roleId: $id);
        $this->messageBus->dispatch($command);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
