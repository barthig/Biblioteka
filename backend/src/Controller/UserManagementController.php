<?php
namespace App\Controller;

use App\Application\Command\User\BlockUserCommand;
use App\Application\Command\User\CreateUserCommand;
use App\Application\Command\User\DeleteUserCommand;
use App\Application\Command\User\UnblockUserCommand;
use App\Application\Command\User\UpdateUserCommand;
use App\Controller\Traits\ValidationTrait;
use App\Request\CreateUserRequest;
use App\Request\UpdateUserRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserManagementController extends AbstractController
{
    use ValidationTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly SecurityService $security
    ) {}
public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
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
        } catch (\RuntimeException $e) {
            $statusCode = str_contains($e->getMessage(), 'Unknown membership group') ? 400 : 500;
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function update(string $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }

        $isAdmin = $this->security->hasRole($request, 'ROLE_ADMIN');
        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        $canManage = $isAdmin || $isLibrarian;
        $payload = $this->security->getJwtPayload($request);
        $isOwner = $payload && isset($payload['sub']) && (int) $payload['sub'] === (int) $id;

        if (!($canManage || $isOwner)) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new UpdateUserRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        // Authorization checks for specific fields
        if (isset($data['roles']) && !$isAdmin) {
            return $this->json(['error' => 'Forbidden to change roles'], 403);
        }
        if ((array_key_exists('pendingApproval', $data) || array_key_exists('verified', $data) || 
             isset($data['membershipGroup']) || isset($data['loanLimit']) || isset($data['blocked'])) && !$isLibrarian) {
            return $this->json(['error' => 'Forbidden to change this field'], 403);
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
        } catch (\RuntimeException $e) {
            $statusCode = match (true) {
                str_contains($e->getMessage(), 'User not found') => 404,
                str_contains($e->getMessage(), 'Unknown membership group') => 400,
                default => 500
            };
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function delete(string $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }

        $command = new DeleteUserCommand(userId: (int) $id);

        try {
            $this->commandBus->dispatch($command);
            return $this->json(null, 204);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    public function block(string $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
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
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    public function unblock(string $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }

        $command = new UnblockUserCommand(userId: (int) $id);

        try {
            $envelope = $this->commandBus->dispatch($command);
            $user = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($user, 200);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

}
