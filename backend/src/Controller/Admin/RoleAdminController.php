<?php
namespace App\Controller\Admin;

use App\Application\Command\StaffRole\CreateStaffRoleCommand;
use App\Application\Command\StaffRole\UpdateStaffRoleCommand;
use App\Application\Command\User\UpdateUserCommand;
use App\Entity\StaffRole;
use App\Entity\User;
use App\Repository\StaffRoleRepository;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin/RoleAdmin')]
class RoleAdminController extends AbstractController
{
    public function __construct(
        private StaffRoleRepository $roles,
        private UserRepository $users,
        private MessageBusInterface $commandBus
    ) {
    }

    #[OA\Get(
        path: '/api/admin/roles',
        summary: 'List staff roles',
        tags: ['Admin/RoleAdmin'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $items = array_map(static function (StaffRole $role): array {
            return [
                'id' => $role->getId(),
                'name' => $role->getName(),
                'roleKey' => $role->getRoleKey(),
                'modules' => $role->getModules(),
                'description' => $role->getDescription(),
            ];
        }, $this->roles->findBy([], ['name' => 'ASC']));

        return $this->json(['roles' => $items], 200);
    }

    #[OA\Post(
        path: '/api/admin/roles',
        summary: 'Create staff role',
        tags: ['Admin/RoleAdmin'],
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
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Already exists', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $roleKey = isset($data['roleKey']) ? trim((string) $data['roleKey']) : '';
        $modules = isset($data['modules']) && is_array($data['modules']) ? $data['modules'] : [];

        if ($name === '' || $roleKey === '') {
            return $this->json(['message' => 'name and roleKey are required'], 400);
        }

        if ($this->roles->findOneBy(['name' => $name]) || $this->roles->findOneByRoleKey($roleKey)) {
            return $this->json(['message' => 'Role already exists'], 409);
        }

        $envelope = $this->commandBus->dispatch(new CreateStaffRoleCommand(
            name: $name,
            roleKey: $roleKey,
            modules: $modules,
            description: $data['description'] ?? null
        ));
        $role = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json([
            'id' => $role->getId(),
            'name' => $role->getName(),
            'roleKey' => $role->getRoleKey(),
            'modules' => $role->getModules(),
        ], 201);
    }

    #[OA\Put(
        path: '/api/admin/roles/{roleKey}',
        summary: 'Update staff role',
        tags: ['Admin/RoleAdmin'],
        parameters: [new OA\Parameter(name: 'roleKey', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(string $roleKey, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $role = $this->roles->findOneByRoleKey($roleKey);
        if (!$role) {
            return $this->json(['message' => 'Role not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $envelope = $this->commandBus->dispatch(new UpdateStaffRoleCommand(
            roleId: $role->getId(),
            modules: isset($data['modules']) && is_array($data['modules']) ? $data['modules'] : null,
            description: array_key_exists('description', $data) ? $data['description'] : null
        ));
        $role = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json([
            'id' => $role->getId(),
            'name' => $role->getName(),
            'roleKey' => $role->getRoleKey(),
            'modules' => $role->getModules(),
            'description' => $role->getDescription(),
        ], 200);
    }

    #[OA\Post(
        path: '/api/admin/roles/{roleKey}/assign',
        summary: 'Assign role to user',
        tags: ['Admin/RoleAdmin'],
        parameters: [new OA\Parameter(name: 'roleKey', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['userId'],
                properties: [new OA\Property(property: 'userId', type: 'integer')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function assign(string $roleKey, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $role = $this->roles->findOneByRoleKey($roleKey);
        if (!$role) {
            return $this->json(['message' => 'Role not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $userId = isset($data['userId']) ? (int) $data['userId'] : 0;
        if ($userId <= 0) {
            return $this->json(['message' => 'Valid userId is required'], 400);
        }

        /** @var User|null $user */
        $user = $this->users->find($userId);
        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $roles = $user->getRoles();
        if (!in_array($role->getRoleKey(), $roles, true)) {
            $roles[] = $role->getRoleKey();
            $roles = array_values(array_unique($roles));
            $this->commandBus->dispatch(new UpdateUserCommand(
                userId: $user->getId(),
                roles: $roles
            ));
        }

        return $this->json([
            'userId' => $user->getId(),
            'roles' => $roles,
        ], 200);
    }
}
