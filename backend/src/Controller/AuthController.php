<?php
namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\JwtService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request, UserRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $email = $data['email'] ?? null;
        if (!$email) return $this->json(['error' => 'Missing email'], 400);

        $user = $repo->findOneBy(['email' => $email]);
        if (!$user) return $this->json(['error' => 'User not found'], 404);

        $token = JwtService::createToken(['sub' => $user->getId(), 'roles' => $user->getRoles()]);
        return $this->json(['token' => $token], 200);
    }
}
