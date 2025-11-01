<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserManagementController extends AbstractController
{
    #[Route('/api/users', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        if (empty($data['email']) || empty($data['name'])) {
            return $this->json(['error' => 'Missing email or name'], 400);
        }

        $user = new User();
        $user->setEmail($data['email'])->setName($data['name'])->setRoles($data['roles'] ?? ['ROLE_USER']);
        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();
        return $this->json($user, 201);
    }

    #[Route('/api/users/{id}', name: 'api_users_update', methods: ['PUT'])]
    public function update(string $id, Request $request, UserRepository $repo, ManagerRegistry $doctrine): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }
        $user = $repo->find((int)$id);
        if (!$user) return $this->json(['error' => 'User not found'], 404);
        $data = json_decode($request->getContent(), true) ?: [];
        if (!empty($data['name'])) $user->setName($data['name']);
        if (!empty($data['email'])) $user->setEmail($data['email']);
        if (isset($data['roles'])) $user->setRoles((array)$data['roles']);
        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();
        return $this->json($user, 200);
    }

    #[Route('/api/users/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(string $id, UserRepository $repo, ManagerRegistry $doctrine): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }
        $user = $repo->find((int)$id);
        if (!$user) return $this->json(['error' => 'User not found'], 404);
        $em = $doctrine->getManager();
        $em->remove($user);
        $em->flush();
        return $this->json(null, 204);
    }
}
