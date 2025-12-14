<?php
namespace App\Controller;

use App\Application\Command\Account\ChangePasswordCommand;
use App\Application\Command\Account\UpdateAccountCommand;
use App\Application\Command\Account\UpdateAccountContactCommand;
use App\Application\Command\Account\UpdateAccountPreferencesCommand;
use App\Application\Command\Account\UpdateAccountUiPreferencesCommand;
use App\Application\Command\Account\UpdateAccountPinCommand;
use App\Application\Command\Account\CompleteOnboardingCommand;
use App\Application\Query\Account\GetAccountDetailsQuery;
use App\Controller\Traits\ValidationTrait;
use App\Request\ChangePasswordRequest;
use App\Request\UpdateAccountRequest;
use App\Service\SecurityService;
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
        private readonly MessageBusInterface $queryBus,
        private readonly SecurityService $security
    ) {}
    public function me(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $envelope = $this->queryBus->dispatch(new GetAccountDetailsQuery($userId));
        $payload = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($payload);
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

    public function updateContact(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $this->commandBus->dispatch(new UpdateAccountContactCommand(
            userId: $userId,
            phoneNumber: $data['phoneNumber'] ?? null,
            addressLine: $data['addressLine'] ?? null,
            city: $data['city'] ?? null,
            postalCode: $data['postalCode'] ?? null
        ));

        return $this->json(['message' => 'Contact information updated']);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        if (isset($data['defaultBranch'])) {
            $user->setDefaultBranch($data['defaultBranch']);
        }
        if (isset($data['newsletter'])) {
            $user->setNewsletterSubscribed($this->normalizeBoolean($data['newsletter']));
        }
        if (isset($data['keepHistory'])) {
            $user->setKeepHistory($this->normalizeBoolean($data['keepHistory']));
        }
        if (isset($data['emailLoans'])) {
            $user->setEmailLoans($this->normalizeBoolean($data['emailLoans']));
        }
        if (isset($data['emailReservations'])) {
            $user->setEmailReservations($this->normalizeBoolean($data['emailReservations']));
        }
        if (isset($data['emailFines'])) {
            $user->setEmailFines($this->normalizeBoolean($data['emailFines']));
        }
        if (isset($data['emailAnnouncements'])) {
            $user->setEmailAnnouncements($this->normalizeBoolean($data['emailAnnouncements']));
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Preferences updated']);
    }

    public function updateUIPreferences(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        if (isset($data['theme'])) {
            $user->setTheme($data['theme']);
        }
        if (isset($data['fontSize'])) {
            $user->setFontSize($data['fontSize']);
        }
        if (isset($data['language'])) {
            $user->setLanguage($data['language']);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'UI preferences updated']);
    }

    public function updatePin(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        if (!isset($data['currentPin']) || !isset($data['newPin'])) {
            return $this->json(['error' => 'Current PIN and new PIN are required'], 400);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // Verify current PIN
        if ($user->getPin() !== $data['currentPin']) {
            return $this->json(['error' => 'Current PIN is incorrect'], 400);
        }

        // Validate new PIN format
        if (!preg_match('/^[0-9]{4}$/', $data['newPin'])) {
            return $this->json(['error' => 'PIN must be 4 digits'], 400);
        }

        $user->setPin($data['newPin']);
        $this->entityManager->flush();

        return $this->json(['message' => 'PIN updated']);
    }

    #[Route('/users/me/onboarding', methods: ['POST'])]
    public function completeOnboarding(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        if (isset($data['preferredCategories']) && is_array($data['preferredCategories'])) {
            $user->setPreferredCategories($data['preferredCategories']);
        }

        $user->setOnboardingCompleted(true);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Onboarding completed',
            'preferredCategories' => $user->getPreferredCategories()
        ]);
    }
}

