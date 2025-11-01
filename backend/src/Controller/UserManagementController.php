<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserManagementController extends AbstractController
{
    public function create(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
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

    public function update(string $id, Request $request, UserRepository $repo, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        // allow librarians to update any user, allow a user to update their own profile
        $isLibrarian = $security->hasRole($request, 'ROLE_LIBRARIAN');
        $payload = $security->getJwtPayload($request);
        $isOwner = $payload && isset($payload['sub']) && (int)$payload['sub'] === (int)$id;
        if (!($isLibrarian || $isOwner)) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
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

    public function delete(string $id, Request $request, UserRepository $repo, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
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
