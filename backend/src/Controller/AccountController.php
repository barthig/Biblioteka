<?php
namespace App\Controller;

use App\Application\Command\Account\ChangePasswordCommand;
use App\Application\Command\Account\UpdateAccountCommand;
use App\Controller\Traits\ValidationTrait;
use App\Entity\User;
use App\Request\ChangePasswordRequest;
use App\Request\UpdateAccountRequest;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AccountController extends AbstractController
{
    use ValidationTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly SecurityService $security,
        private readonly EntityManagerInterface $entityManager
    ) {}
public function me(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'phoneNumber' => $user->getPhoneNumber(),
            'addressLine' => $user->getAddressLine(),
            'city' => $user->getCity(),
            'postalCode' => $user->getPostalCode(),
            'newsletterSubscribed' => $user->isNewsletterSubscribed(),
            'membershipGroup' => $user->getMembershipGroup(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
        ]);
    }

    public function update(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new UpdateAccountRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $command = new UpdateAccountCommand(
            userId: $userId,
            email: $data['email'] ?? null,
            name: $data['name'] ?? null,
            phoneNumber: array_key_exists('phoneNumber', $data) ? (trim((string) $data['phoneNumber']) ?: null) : null,
            addressLine: array_key_exists('addressLine', $data) ? (trim((string) $data['addressLine']) ?: null) : null,
            city: array_key_exists('city', $data) ? (trim((string) $data['city']) ?: null) : null,
            postalCode: array_key_exists('postalCode', $data) ? (trim((string) $data['postalCode']) ?: null) : null,
            newsletterSubscribed: array_key_exists('newsletterSubscribed', $data) ? $this->normalizeBoolean($data['newsletterSubscribed']) : null
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $user = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($user, 200);
        } catch (\RuntimeException $e) {
            $statusCode = match (true) {
                str_contains($e->getMessage(), 'User not found') => 404,
                str_contains($e->getMessage(), 'Email is already taken') => 409,
                str_contains($e->getMessage(), 'cannot be empty') => 400,
                default => 500
            };
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function changePassword(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new ChangePasswordRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }
        
        $command = new ChangePasswordCommand(
            userId: $userId,
            currentPassword: $dto->currentPassword,
            newPassword: $dto->newPassword,
            confirmPassword: $dto->confirmPassword ?? $dto->newPassword
        );

        try {
            $this->commandBus->dispatch($command);
            return $this->json(['message' => 'Hasło zostało zaktualizowane']);
        } catch (\RuntimeException $e) {
            $statusCode = match (true) {
                str_contains($e->getMessage(), 'User not found') => 404,
                default => 400
            };
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
