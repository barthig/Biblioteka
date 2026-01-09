<?php
namespace App\Controller;

use App\Application\Command\User\BlockUserCommand;
use App\Application\Command\User\CreateUserCommand;
use App\Application\Command\User\DeleteUserCommand;
use App\Application\Command\User\UnblockUserCommand;
use App\Application\Command\User\UpdateUserCommand;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Repository\StaffRoleRepository;
use App\Request\CreateUserRequest;
use App\Request\UpdateUserRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'UserManagement')]
class UserManagementController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly SecurityService $security,
        private readonly StaffRoleRepository $staffRoles
    ) {}

    #[OA\Post(
        path: '/api/user-management',
        summary: 'Utwórz użytkownika',
        description: 'Tworzy nowego użytkownika. Wymaga roli LIBRARIAN.',
        tags: ['UserManagement'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'name', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'membershipGroup', type: 'string'),
                    new OA\Property(property: 'loanLimit', type: 'integer'),
                    new OA\Property(property: 'phoneNumber', type: 'string'),
                    new OA\Property(property: 'addressLine', type: 'string'),
                    new OA\Property(property: 'city', type: 'string'),
                    new OA\Property(property: 'postalCode', type: 'string'),
                    new OA\Property(property: 'blocked', type: 'boolean'),
                    new OA\Property(property: 'blockedReason', type: 'string'),
                    new OA\Property(property: 'pendingApproval', type: 'boolean'),
                    new OA\Property(property: 'verified', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Użytkownik utworzony', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new CreateUserRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $command = new CreateUserCommand(
            email: $data['email'],
            name: $data['name'],
            password: $data['password'],
            roles: $data['roles'] ?? ['ROLE_USER'],
            membershipGroup: $data['membershipGroup'] ?? null,
            loanLimit: $data['loanLimit'] ?? null,
            phoneNumber: $data['phoneNumber'] ?? null,
            addressLine: $data['addressLine'] ?? null,
            city: $data['city'] ?? null,
            postalCode: $data['postalCode'] ?? null,
            blocked: $data['blocked'] ?? false,
            blockedReason: $data['blockedReason'] ?? null,
            pendingApproval: $data['pendingApproval'] ?? false,
            verified: $data['verified'] ?? true
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $user = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($user, 201);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            $error = str_contains($e->getMessage(), 'Unknown membership group')
                ? ApiError::badRequest($e->getMessage())
                : ApiError::internalError($e->getMessage());
            return $this->jsonError($error);
        }
    }

    #[OA\Put(
        path: '/api/user-management/{id}',
        summary: 'Aktualizuj użytkownika',
        description: 'Aktualizuje dane użytkownika. Wymaga roli LIBRARIAN lub własności zasobu.',
        tags: ['UserManagement'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'object')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Użytkownik zaktualizowany', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Użytkownik nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function update(string $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid id parameter'));
        }

        $isAdmin = $this->security->hasRole($request, 'ROLE_ADMIN');
        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        $canManage = $isAdmin || $isLibrarian;
        $payload = $this->security->getJwtPayload($request);
        $isOwner = $payload && isset($payload['sub']) && (int) $payload['sub'] === (int) $id;

        if (!($canManage || $isOwner)) {
            return $this->jsonError(ApiError::forbidden());
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new UpdateUserRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        // Authorization checks for specific fields
        if (isset($data['roles']) && !$isAdmin) {
            return $this->jsonError(ApiError::forbidden());
        }
        if ((array_key_exists('pendingApproval', $data) || array_key_exists('verified', $data) || 
             isset($data['membershipGroup']) || isset($data['loanLimit']) || isset($data['blocked'])) && !$isLibrarian) {
            return $this->jsonError(ApiError::forbidden());
        }

        $command = new UpdateUserCommand(
            userId: (int) $id,
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            roles: $data['roles'] ?? null,
            phoneNumber: array_key_exists('phoneNumber', $data) ? (trim((string) $data['phoneNumber']) ?: null) : null,
            addressLine: array_key_exists('addressLine', $data) ? (trim((string) $data['addressLine']) ?: null) : null,
            city: array_key_exists('city', $data) ? (trim((string) $data['city']) ?: null) : null,
            postalCode: array_key_exists('postalCode', $data) ? (trim((string) $data['postalCode']) ?: null) : null,
            pesel: array_key_exists('pesel', $data) ? (trim((string) $data['pesel']) ?: null) : null,
            cardNumber: array_key_exists('cardNumber', $data) ? (trim((string) $data['cardNumber']) ?: null) : null,
            pendingApproval: array_key_exists('pendingApproval', $data) ? (bool) $data['pendingApproval'] : null,
            verified: array_key_exists('verified', $data) ? (bool) $data['verified'] : null,
            membershipGroup: $data['membershipGroup'] ?? null,
            loanLimit: $data['loanLimit'] ?? null,
            blocked: $data['blocked'] ?? null,
            blockedReason: $data['blockedReason'] ?? null
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $user = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($user, 200);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            $error = match (true) {
                str_contains($e->getMessage(), 'User not found') => ApiError::notFound('User'),
                str_contains($e->getMessage(), 'Unknown membership group') => ApiError::badRequest($e->getMessage()),
                default => ApiError::internalError($e->getMessage())
            };
            return $this->jsonError($error);
        }
    }

    #[OA\Delete(
        path: '/api/user-management/{id}',
        summary: 'Usuń użytkownika',
        description: 'Usuwa użytkownika. Wymaga roli LIBRARIAN.',
        tags: ['UserManagement'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Użytkownik usunięty'),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Użytkownik nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function delete(string $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid id parameter'));
        }

        $command = new DeleteUserCommand(userId: (int) $id);

        try {
            $this->commandBus->dispatch($command);
            return $this->json(null, 204);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->json(['message' => $e->getMessage()], 404);
        }
    }

    #[OA\Put(
        path: '/api/user-management/{id}/permissions',
        summary: 'Aktualizuj uprawnienia użytkownika',
        description: 'Aktualizuje role/uprawnienia użytkownika. Wymaga roli ADMIN.',
        tags: ['UserManagement'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['roles'],
                properties: [
                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Uprawnienia zaktualizowane', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Użytkownik nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function updatePermissions(string $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonError(ApiError::forbidden());
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid id parameter'));
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->jsonError(ApiError::badRequest('Invalid JSON payload'));
        }

        $roles = $data['roles'] ?? $data['permissions'] ?? null;
        if (!is_array($roles) || count($roles) === 0) {
            return $this->jsonError(ApiError::badRequest('roles are required'));
        }

        $roles = array_values(array_unique(array_filter(array_map(static function ($role) {
            $role = strtoupper(trim((string) $role));
            if ($role === '') {
                return null;
            }
            return str_starts_with($role, 'ROLE_') ? $role : 'ROLE_' . $role;
        }, $roles))));

        if (count($roles) === 0) {
            return $this->jsonError(ApiError::badRequest('roles are required'));
        }

        $allowedBaseRoles = ['ROLE_USER', 'ROLE_LIBRARIAN', 'ROLE_ADMIN', 'ROLE_SYSTEM'];
        $invalidRoles = [];
        foreach ($roles as $role) {
            if (in_array($role, $allowedBaseRoles, true)) {
                continue;
            }
            if ($this->staffRoles->findOneByRoleKey($role)) {
                continue;
            }
            $invalidRoles[] = $role;
        }

        if (count($invalidRoles) > 0) {
            return $this->jsonError(ApiError::badRequest('Invalid roles', ['invalidRoles' => $invalidRoles]));
        }

        $command = new UpdateUserCommand(
            userId: (int) $id,
            roles: $roles
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $user = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($user, 200);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonError(ApiError::internalError($e->getMessage()));
        }
    }

    #[OA\Post(
        path: '/api/user-management/{id}/block',
        summary: 'Zablokuj użytkownika',
        description: 'Blokuje konto użytkownika. Wymaga roli LIBRARIAN.',
        tags: ['UserManagement'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'reason', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Użytkownik zablokowany', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Użytkownik nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function block(string $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid id parameter'));
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $command = new BlockUserCommand(
            userId: (int) $id,
            reason: $data['reason'] ?? null
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $user = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($user, 200);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonError(ApiError::notFound('User'));
        }
    }

    #[OA\Post(
        path: '/api/user-management/{id}/unblock',
        summary: 'Odblokuj użytkownika',
        description: 'Odblokowuje konto użytkownika. Wymaga roli LIBRARIAN.',
        tags: ['UserManagement'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Użytkownik odblokowany', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Użytkownik nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function unblock(string $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid id parameter'));
        }

        $command = new UnblockUserCommand(userId: (int) $id);

        try {
            $envelope = $this->commandBus->dispatch($command);
            $user = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($user, 200);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->json(['message' => $e->getMessage()], 404);
        }
    }

}
