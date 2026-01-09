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
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Request\ChangePasswordRequest;
use App\Request\UpdateAccountRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Account')]
class AccountController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
        private readonly SecurityService $security
    ) {}
    #[OA\Get(
        path: '/api/me',
        summary: 'Get current account details',
        tags: ['Account'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $envelope = $this->queryBus->dispatch(new GetAccountDetailsQuery($userId));
        $payload = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($payload);
    }

    #[OA\Put(
        path: '/api/me',
        summary: 'Update account profile',
        tags: ['Account'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'phoneNumber', type: 'string', nullable: true),
                    new OA\Property(property: 'addressLine', type: 'string', nullable: true),
                    new OA\Property(property: 'city', type: 'string', nullable: true),
                    new OA\Property(property: 'postalCode', type: 'string', nullable: true),
                    new OA\Property(property: 'newsletterSubscribed', type: 'boolean', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Email conflict', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
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
        } catch (\Throwable $e) {
            return $this->handleCommandException($e);
        }
    }

    #[OA\Put(
        path: '/api/me/password',
        summary: 'Change account password',
        tags: ['Account'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['currentPassword', 'newPassword'],
                properties: [
                    new OA\Property(property: 'currentPassword', type: 'string', format: 'password'),
                    new OA\Property(property: 'newPassword', type: 'string', format: 'password'),
                    new OA\Property(property: 'confirmPassword', type: 'string', format: 'password', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function changePassword(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
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
        } catch (\Throwable $e) {
            return $this->handleCommandException($e);
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

    /**
     * Handles command exceptions and returns appropriate JSON response
     */
    private function handleCommandException(\Throwable $e): JsonResponse
    {
        if ($e instanceof HandlerFailedException) {
            $e = $e->getPrevious() ?? $e;
        }
        
        if ($e instanceof HttpExceptionInterface) {
            return $this->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
        
        $statusCode = match (true) {
            str_contains($e->getMessage(), 'User not found') => 404,
            str_contains($e->getMessage(), 'Email is already taken') => 409,
            str_contains($e->getMessage(), 'cannot be empty') => 400,
            str_contains($e->getMessage(), 'Invalid password') => 400,
            str_contains($e->getMessage(), 'Current password') => 400,
            default => 500
        };
        
        return $this->json(['message' => $e->getMessage()], $statusCode);
    }

    #[OA\Put(
        path: '/api/me/contact',
        summary: 'Update contact info',
        tags: ['Account'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'phoneNumber', type: 'string', nullable: true),
                    new OA\Property(property: 'addressLine', type: 'string', nullable: true),
                    new OA\Property(property: 'city', type: 'string', nullable: true),
                    new OA\Property(property: 'postalCode', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updateContact(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
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

    #[OA\Put(
        path: '/api/me/preferences',
        summary: 'Update account preferences',
        tags: ['Account'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'defaultBranch', type: 'string', nullable: true),
                    new OA\Property(property: 'newsletter', type: 'boolean', nullable: true),
                    new OA\Property(property: 'keepHistory', type: 'boolean', nullable: true),
                    new OA\Property(property: 'emailLoans', type: 'boolean', nullable: true),
                    new OA\Property(property: 'emailReservations', type: 'boolean', nullable: true),
                    new OA\Property(property: 'emailFines', type: 'boolean', nullable: true),
                    new OA\Property(property: 'emailAnnouncements', type: 'boolean', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updatePreferences(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $this->commandBus->dispatch(new UpdateAccountPreferencesCommand(
            userId: $userId,
            defaultBranch: $data['defaultBranch'] ?? null,
            newsletterSubscribed: isset($data['newsletter']) ? $this->normalizeBoolean($data['newsletter']) : null,
            keepHistory: isset($data['keepHistory']) ? $this->normalizeBoolean($data['keepHistory']) : null,
            emailLoans: isset($data['emailLoans']) ? $this->normalizeBoolean($data['emailLoans']) : null,
            emailReservations: isset($data['emailReservations']) ? $this->normalizeBoolean($data['emailReservations']) : null,
            emailFines: isset($data['emailFines']) ? $this->normalizeBoolean($data['emailFines']) : null,
            emailAnnouncements: isset($data['emailAnnouncements']) ? $this->normalizeBoolean($data['emailAnnouncements']) : null
        ));

        return $this->json(['message' => 'Preferences updated']);
    }

    #[OA\Put(
        path: '/api/me/ui-preferences',
        summary: 'Update UI preferences',
        tags: ['Account'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'theme', type: 'string', nullable: true),
                    new OA\Property(property: 'fontSize', type: 'string', nullable: true),
                    new OA\Property(property: 'language', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updateUIPreferences(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $this->commandBus->dispatch(new UpdateAccountUiPreferencesCommand(
            userId: $userId,
            theme: $data['theme'] ?? null,
            fontSize: $data['fontSize'] ?? null,
            language: $data['language'] ?? null
        ));

        return $this->json(['message' => 'UI preferences updated']);
    }

    #[OA\Put(
        path: '/api/me/pin',
        summary: 'Update account PIN',
        tags: ['Account'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['currentPin', 'newPin'],
                properties: [
                    new OA\Property(property: 'currentPin', type: 'string'),
                    new OA\Property(property: 'newPin', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updatePin(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];

        if (!isset($data['currentPin']) || !isset($data['newPin'])) {
            return $this->json(['message' => 'Current PIN and new PIN are required'], 400);
        }
        try {
            $this->commandBus->dispatch(new UpdateAccountPinCommand(
                userId: $userId,
                currentPin: (string) $data['currentPin'],
                newPin: (string) $data['newPin']
            ));
        } catch (\RuntimeException|HttpExceptionInterface $e) {
            return $this->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return $this->json(['message' => 'PIN updated']);
    }

    #[OA\Post(
        path: '/api/users/me/onboarding',
        summary: 'Complete onboarding',
        tags: ['Account'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'preferredCategories',
                        type: 'array',
                        items: new OA\Items(type: 'string')
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(
                            property: 'preferredCategories',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[Route('/users/me/onboarding', methods: ['POST'])]
    public function completeOnboarding(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $envelope = $this->commandBus->dispatch(new CompleteOnboardingCommand(
            userId: $userId,
            preferredCategories: isset($data['preferredCategories']) && is_array($data['preferredCategories'])
                ? $data['preferredCategories']
                : null
        ));
        $user = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json([
            'message' => 'Onboarding completed',
            'preferredCategories' => $user?->getPreferredCategories()
        ]);
    }
}

