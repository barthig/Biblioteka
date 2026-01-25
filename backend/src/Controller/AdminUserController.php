<?php
namespace App\Controller;

use App\Application\Command\User\DeleteUserCommand;
use App\Application\Command\User\UpdateUserCommand;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

class AdminUserController extends AbstractController
{
    use ExceptionHandlingTrait;

    #[OA\Tag(name: 'AdminUser')]
    public function __construct(
        private readonly SecurityService $security,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        path: '/api/admin/users/{id}',
        summary: 'Update user (admin only)',
        description: 'Allows administrators to update user details including roles, account status, and blocking status',
        tags: ['AdminUser'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'User ID',
                schema: new OA\Schema(type: 'integer', example: 42)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'User data to update',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Jan Kowalski'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jan.kowalski@example.com'),
                    new OA\Property(
                        property: 'roles',
                        type: 'array',
                        items: new OA\Items(type: 'string', enum: ['ROLE_USER', 'ROLE_LIBRARIAN', 'ROLE_ADMIN']),
                        example: ['ROLE_USER', 'ROLE_LIBRARIAN']
                    ),
                    new OA\Property(property: 'cardNumber', type: 'string', example: '123456789'),
                    new OA\Property(property: 'accountStatus', type: 'string', enum: ['active', 'inactive', 'suspended'], example: 'active'),
                    new OA\Property(property: 'blocked', type: 'boolean', example: false),
                    new OA\Property(property: 'blockedReason', type: 'string', nullable: true, example: null)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User updated successfully'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 42),
                                new OA\Property(property: 'name', type: 'string', example: 'Jan Kowalski'),
                                new OA\Property(property: 'email', type: 'string', example: 'jan.kowalski@example.com')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Admin role required', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $envelope = $this->commandBus->dispatch(new UpdateUserCommand(
            userId: $id,
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            roles: isset($data['roles']) && is_array($data['roles']) ? $data['roles'] : null,
            cardNumber: $data['cardNumber'] ?? null,
            accountStatus: $data['accountStatus'] ?? null,
            blocked: array_key_exists('blocked', $data) ? (bool) $data['blocked'] : null,
            blockedReason: $data['blockedReason'] ?? null
        ));

        $user = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ]
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/admin/users/{id}',
        summary: 'Delete user (admin only)',
        description: 'Permanently deletes a user account. Cannot delete own account. Use with caution.',
        tags: ['AdminUser'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'User ID to delete',
                schema: new OA\Schema(type: 'integer', example: 42)
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'User deleted successfully (no content)'
            ),
            new OA\Response(
                response: 400,
                description: 'Cannot delete own account',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cannot delete your own account')
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden - Admin role required', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function delete(int $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $currentUserId = $this->security->getCurrentUserId($request);
        if ($currentUserId === $id) {
            return $this->jsonErrorMessage(400, 'Cannot delete your own account');
        }

        $this->commandBus->dispatch(new DeleteUserCommand($id));

        return new JsonResponse(null, 204);
    }
}

