<?php
namespace App\Controller;

use App\Application\Command\User\DeleteUserCommand;
use App\Application\Command\User\UpdateUserCommand;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly SecurityService $security,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request, UserRepository $repo): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
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
    public function delete(int $id, Request $request, UserRepository $repo): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $this->commandBus->dispatch(new DeleteUserCommand($id));

        return new JsonResponse(null, 204);
    }
}
