<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends AbstractController
{
    public function list(UserRepository $repo): JsonResponse
    {
        $users = $repo->findAll();
        return $this->json($users, 200);
    }

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
