<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'api_users_list', methods: ['GET'])]
    public function list(UserRepository $repo): JsonResponse
    {
        $users = $repo->findAll();
        return $this->json($users, 200);
    }

    #[Route('/api/users/{id}', name: 'api_users_get', methods: ['GET'])]
    public function getUserById(string $id, UserRepository $repo): JsonResponse
    {
        // validate id — should be positive integer
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }
        $user = $repo->find((int)$id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }
        return $this->json($user, 200);
    }
}
