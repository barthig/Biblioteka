<?php
namespace App\Controller;

use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Request\RegistrationRequest;
use App\Service\RegistrationException;
use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Registration')]
class RegistrationController extends AbstractController
{
    use ExceptionHandlingTrait;
    use ValidationTrait;

    public function __construct(
        private RateLimiterFactory $registrationAttemptsLimiter,
    ) {
    }

    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Register new account',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'name', 'password', 'privacyConsent'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'phoneNumber', type: 'string', nullable: true),
                    new OA\Property(property: 'addressLine', type: 'string', nullable: true),
                    new OA\Property(property: 'city', type: 'string', nullable: true),
                    new OA\Property(property: 'postalCode', type: 'string', nullable: true),
                    new OA\Property(property: 'membershipGroup', type: 'string', nullable: true),
                    new OA\Property(property: 'privacyConsent', type: 'boolean'),
                    new OA\Property(property: 'newsletterSubscribed', type: 'boolean', nullable: true),
                    new OA\Property(property: 'tastePrompt', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'pending_verification'),
                        new OA\Property(property: 'userId', type: 'integer'),
                        new OA\Property(property: 'verificationToken', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Internal error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function register(Request $request, RegistrationService $registrationService, ValidatorInterface $validator): JsonResponse
    {
        // Rate limiting tymczasowo wyłączone
        // $limiter = $this->registrationAttemptsLimiter->create($request->getClientIp());
        // if (!$limiter->consume(1)->isAccepted()) {
        //     return $this->json(['message' => 'Zbyt wiele prób rejestracji. Spróbuj ponownie później.'], 429);
        // }
        
        $data = json_decode($request->getContent(), true) ?: [];

        $dto = $this->mapArrayToDto($data, new RegistrationRequest());
        $errors = $validator->validate($dto);
        
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        // Konwertuj z powrotem do tablicy dla RegistrationService
        $payload = [
            'email' => $dto->email,
            'name' => $dto->name,
            'password' => $dto->password,
            'phoneNumber' => $dto->phoneNumber,
            'addressLine' => $dto->addressLine,
            'city' => $dto->city,
            'postalCode' => $dto->postalCode,
            'membershipGroup' => $dto->membershipGroup,
            'privacyConsent' => $dto->privacyConsent,
        ];

        // newsletterSubscribed powinien być przekazany tylko jeśli został jawnie ustawiony
        if (array_key_exists('newsletterSubscribed', $data)) {
            $payload['newsletterSubscribed'] = $dto->newsletterSubscribed;
        }

        if (array_key_exists('tastePrompt', $data)) {
            $payload['tastePrompt'] = $dto->tastePrompt;
        }

        try {
            $token = $registrationService->register($payload);
        } catch (RegistrationException $exception) {
            $status = $exception->getCode();
            if ($status < 400 || $status >= 600) {
                $status = 400;
            }

            return $this->jsonErrorMessage($status, $exception->getMessage());
        } catch (\Throwable $error) {
            return $this->jsonErrorMessage(500, 'Nie udało się utworzyć konta.');
        }

        return $this->json([
            'status' => 'pending_verification',
            'userId' => $token->getUser()->getId(),
            'verificationToken' => $token->getToken(),
        ], 201);
    }

    #[OA\Get(
        path: '/api/auth/verify/{token}',
        summary: 'Verify registration token',
        tags: ['Auth'],
        security: [],
        parameters: [
            new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'account_verified'),
                        new OA\Property(property: 'userId', type: 'integer'),
                        new OA\Property(property: 'pendingApproval', type: 'boolean'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid token', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Internal error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function verify(string $token, RegistrationService $registrationService): JsonResponse
    {
        try {
            $user = $registrationService->verify($token);
        } catch (RegistrationException $exception) {
            $status = $exception->getCode();
            if ($status < 400 || $status >= 600) {
                $status = 400;
            }

            return $this->jsonErrorMessage($status, $exception->getMessage());
        } catch (\Throwable $error) {
            return $this->jsonErrorMessage(500, 'Nie udało się zweryfikować konta.');
        }

        $response = [
            'status' => 'account_verified',
            'userId' => $user->getId(),
            'pendingApproval' => $user->isPendingApproval(),
        ];

        return $this->json($response, 200);
    }
}
