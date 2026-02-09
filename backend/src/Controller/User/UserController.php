<?php
declare(strict_types=1);
namespace App\Controller\User;

use App\Application\Query\User\GetUserDetailsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Auth\SecurityService;
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
        private readonly SecurityService $security,
        private readonly MessageBusInterface $messageBus
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
            return $this->jsonError(ApiError::forbidden());
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
            return $this->jsonError(ApiError::badRequest('Invalid id parameter'));
        }
        
        $userId = (int)$id;
        $currentUserId = $this->security->getCurrentUserId($request);
        
        // Users can only see their own profile unless they're librarians
        if ($currentUserId !== $userId && !$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $user = $repo->find($userId);
        if (!$user) {
            return $this->jsonError(ApiError::notFound('User not found'));
        }
        
        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    public function search(UserRepository $repo, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
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
            return $this->jsonError(ApiError::forbidden());
        }

        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid id parameter'));
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
            return $this->jsonError(ApiError::notFound($e->getMessage()));
        }
    }
}

