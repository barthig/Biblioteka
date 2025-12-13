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
            'pesel' => $user->getPesel(),
            'cardNumber' => $user->getCardNumber(),
            'cardExpiry' => $user->getCardExpiry()?->format('Y-m-d'),
            'accountStatus' => $user->getAccountStatus() ?? 'Aktywne',
            'newsletterSubscribed' => $user->isNewsletterSubscribed(),
            'newsletter' => $user->isNewsletterSubscribed(),
            'keepHistory' => $user->getKeepHistory() ?? false,
            'emailLoans' => $user->getEmailLoans() ?? true,
            'emailReservations' => $user->getEmailReservations() ?? true,
            'emailFines' => $user->getEmailFines() ?? true,
            'emailAnnouncements' => $user->getEmailAnnouncements() ?? false,
            'preferredContact' => $user->getPreferredContact() ?? 'email',
            'defaultBranch' => $user->getDefaultBranch(),
            'theme' => $user->getTheme() ?? 'auto',
            'fontSize' => $user->getFontSize() ?? 'standard',
            'language' => $user->getLanguage() ?? 'pl',
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

    public function updateContact(Request $request): JsonResponse
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

        if (isset($data['phoneNumber'])) {
            $user->setPhoneNumber($data['phoneNumber']);
        }
        if (isset($data['addressLine'])) {
            $user->setAddressLine($data['addressLine']);
        }
        if (isset($data['city'])) {
            $user->setCity($data['city']);
        }
        if (isset($data['postalCode'])) {
            $user->setPostalCode($data['postalCode']);
        }
        if (isset($data['preferredContact'])) {
            $user->setPreferredContact($data['preferredContact']);
        }

        $this->entityManager->flush();

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

