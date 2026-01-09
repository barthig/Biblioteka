<?php
namespace App\Controller;

use App\Application\Command\User\DeleteUserCommand;
use App\Application\Command\User\UpdateUserCommand;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

class AdminUserController extends AbstractController
{
    #[OA\Tag(name: 'AdminUser')]
    public function __construct(
        private readonly SecurityService $security,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        path: '/api/admin/users/{id}',
        summary: 'Update user (admin)',
        tags: ['AdminUser'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'object')
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
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
        summary: 'Delete user (admin)',
        tags: ['AdminUser'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 400, description: 'Bad request', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function delete(int $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $currentUserId = $this->security->getCurrentUserId($request);
        if ($currentUserId === $id) {
            return $this->json(['message' => 'Cannot delete your own account'], 400);
        }

        $this->commandBus->dispatch(new DeleteUserCommand($id));

        return new JsonResponse(null, 204);
    }
}
