<?php

namespace App\Controller\Auth;

use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Repository\UserRepository;
use App\Service\JwtService;
use App\Service\RefreshTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'TestAuth')]
class TestAuthController extends AbstractController
{
    use ExceptionHandlingTrait;
    
    public function __construct(
        private RefreshTokenService $refreshTokenService
    ) {
    }

    #[OA\Post(
        path: '/api/test-login',
        summary: 'Test login (tylko dev/test)',
        description: 'Endpoint testowy do logowania bez weryfikacji hasła (dostępny tylko w środowisku dev/test)',
        tags: ['TestAuth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'password', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Nieprawidłowe hasło', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Użytkownik nie znaleziony lub endpoint niedostępny', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function testLogin(Request $request, UserRepository $repo): JsonResponse
    {
        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod');
        if (!in_array($env, ['dev', 'test'], true)) {
            return $this->jsonErrorMessage(404, 'Not found');
        }

        try {
            $data = json_decode($request->getContent(), true) ?: [];
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            $password = $data['password'] ?? '';

            if (!$email || !$password) {
                return $this->jsonErrorMessage(400, 'Email i hasło są wymagane');
            }

            $user = $repo->findOneBy(['email' => $email]);
            if (!$user) {
                return $this->jsonErrorMessage(404, 'Użytkownik nie znaleziony');
            }

            if (!password_verify($password, $user->getPassword())) {
                return $this->json([
                    'error' => 'Nieprawidłowe hasło',
                    'hashLength' => strlen($user->getPassword()),
                    'passwordLength' => strlen($password)
                ], 401);
            }

            // Test JWT
            $token = JwtService::createToken([
                'sub' => $user->getId(),
                'roles' => $user->getRoles(),
                'email' => $user->getEmail(),
                'name' => $user->getName()
            ]);

            // Test Refresh Token
            $refreshToken = $this->refreshTokenService->createRefreshToken($user, $request);

            return $this->json([
                'success' => true,
                'userId' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'token' => $token,
                'refreshToken' => $refreshToken->getToken()
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Exception: ' . $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 10)
            ], 500);
        }
    }
}

