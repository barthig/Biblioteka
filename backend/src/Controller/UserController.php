<?php
namespace App\Controller;

use App\Application\Query\User\GetUserDetailsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'User')]
class UserController extends AbstractController
{
    use HandleTrait;
    use ExceptionHandlingTrait;

    public function __construct(
        private SecurityService $security,
        private MessageBusInterface $messageBus
    ) {
    }

    #[OA\Get(
        path: '/api/users',
        summary: 'List users',
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/User')
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[OA\Get(
        path: '/api/users/search',
        summary: 'Search users',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/User')
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[OA\Get(
        path: '/api/users/{id}/details',
        summary: 'Get user details (loans and fines)',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[IsGranted('ROLE_LIBRARIAN')]
    public function list(UserRepository $repo, Request $request): JsonResponse
    {
        // Only librarians can list all users
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $users = array_values(array_filter(
            $repo->findAll(),
            static fn(User $user) => !in_array('ROLE_SYSTEM', $user->getRoles(), true)
        ));
        return $this->json($users, 200, [], ['groups' => ['user:read']]);
    }

    #[OA\Get(
        path: '/api/users/{id}',
        summary: 'Get user by id',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function getUserById(string $id, UserRepository $repo, Request $request): JsonResponse
    {
        // validate id — should be positive integer
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->jsonErrorMessage(400, 'Invalid id parameter');
        }
        
        $userId = (int)$id;
        $currentUserId = $this->security->getCurrentUserId($request);
        
        // Users can only see their own profile unless they're librarians
        if ($currentUserId !== $userId && !$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $user = $repo->find($userId);
        if (!$user) {
            return $this->jsonErrorMessage(404, 'User not found');
        }
        
        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    public function search(UserRepository $repo, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $query = $request->query->get('q', '');
        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $users = $repo->searchUsers($query);
        return $this->json($users, 200, [], ['groups' => ['user:read']]);
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    public function getUserDetails(string $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->jsonErrorMessage(400, 'Invalid id parameter');
        }

        try {
            $result = $this->handle(new GetUserDetailsQuery((int)$id));
            
            return $this->json($result, 200, [], [
                'groups' => ['user:read', 'loan:read', 'fine:read']
            ]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonErrorMessage(404, $e->getMessage());
        }
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        path: '/api/users/{id}',
        summary: 'Aktualizuj użytkownika',
        description: 'Aktualizuje dane użytkownika. Wymaga roli ADMIN.',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'active', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Użytkownik zaktualizowany', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Użytkownik nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function update(string $id, UserRepository $repo, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $userId = (int)$id;
        $user = $repo->find($userId);
        if (!$user) {
            return $this->jsonErrorMessage(404, 'User not found');
        }

        $data = json_decode($request->getContent(), true) ?? [];
        
        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }
        
        if (isset($data['active']) && is_bool($data['active'])) {
            if ($data['active']) {
                $user->unblock();
            } else {
                $user->block('Deactivated by admin');
            }
        }

        $repo->save($user, true);
        
        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/users/{id}',
        summary: 'Usuń użytkownika',
        description: 'Usuwa użytkownika. Wymaga roli ADMIN. Nie można usunąć własnego konta.',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Użytkownik usunięty'),
            new OA\Response(response: 400, description: 'Nie można usunąć własnego konta', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Użytkownik nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function delete(string $id, UserRepository $repo, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $userId = (int)$id;
        $currentUserId = $this->security->getCurrentUserId($request);
        
        // Prevent admin from deleting themselves
        if ($userId === $currentUserId) {
            return $this->jsonErrorMessage(400, 'Cannot delete your own account');
        }

        $user = $repo->find($userId);
        if (!$user) {
            return $this->jsonErrorMessage(404, 'User not found');
        }

        $repo->remove($user, true);

        return new JsonResponse(null, 204);
    }

    #[OA\Put(
        path: '/api/users/me/password',
        summary: 'Change current user password',
        tags: ['User'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'oldPassword', type: 'string', example: 'oldpass123'),
                    new OA\Property(property: 'newPassword', type: 'string', example: 'newpass123')
                ],
                required: ['oldPassword', 'newPassword']
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password changed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Password changed successfully')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid old password or validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function changePassword(Request $request, UserRepository $repo): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['oldPassword']) || !isset($data['newPassword'])) {
                return $this->json([
                    'message' => 'Missing required fields: oldPassword, newPassword'
                ], 400);
            }

            $oldPassword = $data['oldPassword'];
            $newPassword = $data['newPassword'];

            // Validate new password
            if (strlen($newPassword) < 6) {
                return $this->json([
                    'message' => 'New password must be at least 6 characters long'
                ], 400);
            }

            $currentUserId = $this->security->getCurrentUserId($request);
            $user = $repo->find($currentUserId);

            if (!$user) {
                return $this->jsonErrorMessage(404, 'User not found');
            }

            // Verify old password
            if (!password_verify($oldPassword, $user->getPassword())) {
                return $this->json([
                    'message' => 'Invalid old password'
                ], 400);
            }

            // Hash and set new password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $user->setPassword($hashedPassword);
            $repo->save($user, true);

            return $this->json([
                'message' => 'Password changed successfully'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Failed to change password: ' . $e->getMessage()
            ], 500);
        }
    }
}
