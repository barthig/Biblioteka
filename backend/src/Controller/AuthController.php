<?php
namespace App\Controller;

use App\Application\Query\User\GetUserByIdQuery;
use App\Repository\UserRepository;
use App\Request\LoginRequest;
use App\Service\JwtService;
use App\Service\RefreshTokenService;
use App\Service\SecurityService;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth')]
class AuthController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private RateLimiterFactory $loginAttemptsLimiter,
        private RefreshTokenService $refreshTokenService,
    ) {
    }

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'User login',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
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
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'refreshToken', type: 'string'),
                        new OA\Property(property: 'expiresIn', type: 'integer', example: 86400),
                        new OA\Property(property: 'refreshExpiresIn', type: 'integer', example: 2592000),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Invalid credentials', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Account blocked or not verified', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Internal error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function login(Request $request, UserRepository $repo, ValidatorInterface $validator, LoggerInterface $logger): JsonResponse
    {
        $ip = $request->getClientIp() ?? 'unknown';
        $limiter = $this->loginAttemptsLimiter->create($ip);

        try {
            $data = json_decode($request->getContent(), true) ?: [];
            $loginRequest = new LoginRequest();
            $loginRequest->email = isset($data['email']) ? strtolower(trim((string) $data['email'])) : '';
            $loginRequest->password = $data['password'] ?? null;

            $errors = $validator->validate($loginRequest);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                return $this->jsonError(ApiError::badRequest(implode(', ', $messages)));
            }

            $email = $loginRequest->email;
            $password = $loginRequest->password;

            $user = $repo->findOneBy(['email' => $email]);
            if (!$user || !password_verify($password, $user->getPassword())) {
                $limit = $limiter->consume(1);
                if (!$limit->isAccepted()) {
                    return $this->jsonError(ApiError::tooManyRequests('Zbyt wiele prób logowania. Spróbuj ponownie za 15 minut.'));
                }
                return $this->jsonError(ApiError::unauthorized());
            }

            // Successful login resets limiter attempts
            $limiter->reset();

            $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? null);
            if ($appEnv !== 'test' && !$user->isVerified()) {
                return $this->jsonError(ApiError::forbidden());
            }

            if ($user->isPendingApproval()) {
                return $this->jsonError(ApiError::forbidden());
            }

            if ($user->isBlocked()) {
                return $this->jsonError(ApiError::forbidden());
            }

            $token = JwtService::createToken([
                'sub' => $user->getId(),
                'roles' => $user->getRoles(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
            ]);
            
            try {
                $refreshToken = $this->refreshTokenService->createRefreshToken($user, $request);
                $refreshTokenString = $refreshToken->getToken();
            } catch (\Throwable $refreshError) {
                $logger->error('Failed to create refresh token', [
                    'error' => $refreshError->getMessage(),
                    'trace' => substr($refreshError->getTraceAsString(), 0, 1000),
                    'file' => $refreshError->getFile(),
                    'line' => $refreshError->getLine(),
                ]);
                return $this->jsonError(ApiError::internalError('Failed to create session. Please try again.'));
            }
            
            return $this->json([
                'token' => $token,
                'refreshToken' => $refreshTokenString,
                'expiresIn' => 86400,
                'refreshExpiresIn' => 2592000,
            ], 200);
        } catch (\Throwable $e) {
            $logger->error('Login error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->jsonError(ApiError::internalError('Wystąpił błąd logowania'));
        }
    }

    #[OA\Get(
        path: '/api/profile',
        summary: 'Get authenticated user profile',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[OA\Get(
        path: '/api/auth/profile',
        summary: 'Get authenticated user profile',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function profile(Request $request, MessageBusInterface $queryBus): JsonResponse
    {
        $payload = $request->attributes->get('jwt_payload');
        if (!$payload) {
            return $this->jsonError(ApiError::unauthorized());
        }

        $userId = $payload['sub'] ?? null;
        if (!$userId) {
            return $this->jsonError(ApiError::unauthorized());
        }

        $envelope = $queryBus->dispatch(new GetUserByIdQuery($userId));
        $user = $envelope->last(HandledStamp::class)?->getResult();
        if (!$user) {
            return $this->jsonError(ApiError::notFound('User'));
        }

        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Refresh JWT using refresh token',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refreshToken'],
                properties: [
                    new OA\Property(property: 'refreshToken', type: 'string'),
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
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'refreshToken', type: 'string'),
                        new OA\Property(property: 'expiresIn', type: 'integer', example: 86400),
                        new OA\Property(property: 'refreshExpiresIn', type: 'integer', example: 2592000),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Missing refresh token', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Invalid refresh token', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Internal error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $refreshTokenString = $data['refreshToken'] ?? null;

        if (!$refreshTokenString) {
            return $this->jsonError(ApiError::badRequest('Refresh token is required'));
        }

        $user = $this->refreshTokenService->validateRefreshToken($refreshTokenString);

        if (!$user) {
            return $this->jsonError(ApiError::unauthorized());
        }

        $this->refreshTokenService->revokeRefreshToken($refreshTokenString);
        
        try {
            $newRefreshToken = $this->refreshTokenService->createRefreshToken($user, $request);
            $newRefreshTokenString = $newRefreshToken->getToken();
        } catch (\Throwable $e) {
            return $this->jsonError(ApiError::internalError('Failed to rotate refresh token'));
        }

        $token = JwtService::createToken([
            'sub' => $user->getId(),
            'roles' => $user->getRoles(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
        ]);

        return $this->json([
            'token' => $token,
            'refreshToken' => $newRefreshTokenString,
            'expiresIn' => 86400,
            'refreshExpiresIn' => 2592000,
        ], 200);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout (revoke refresh token)',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'refreshToken', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $refreshTokenString = $data['refreshToken'] ?? null;

        if ($refreshTokenString) {
            $this->refreshTokenService->revokeRefreshToken($refreshTokenString);
        }

        return $this->jsonSuccess(['message' => 'Logged out successfully']);
    }

    #[OA\Post(
        path: '/api/auth/logout-all',
        summary: 'Logout from all sessions',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'revokedCount', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function logoutAll(Request $request, MessageBusInterface $queryBus): JsonResponse
    {
        $payload = $request->attributes->get('jwt_payload');
        if (!$payload) {
            return $this->jsonError(ApiError::unauthorized());
        }

        $envelope = $queryBus->dispatch(new GetUserByIdQuery($payload['sub']));
        $user = $envelope->last(HandledStamp::class)?->getResult();
        if (!$user) {
            return $this->jsonError(ApiError::notFound('User'));
        }

        $count = $this->refreshTokenService->revokeAllUserTokens($user);

        return $this->json([
            'message' => 'All sessions terminated',
            'revokedCount' => $count,
        ], 200);
    }
}
