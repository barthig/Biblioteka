<?php
namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly SecurityService $security,
        private readonly EntityManagerInterface $em
    ) {
    }

    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request, UserRepository $repo): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $user = $repo->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['cardNumber'])) {
            $user->setCardNumber($data['cardNumber']);
        }

        if (isset($data['accountStatus'])) {
            $user->setAccountStatus($data['accountStatus']);
        }

        if (isset($data['blocked'])) {
            if ((bool)$data['blocked']) {
                $user->block();
            } else {
                $user->unblock();
            }
        }

        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        $this->em->flush();

        return $this->json([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ]
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, Request $request, UserRepository $repo): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $user = $repo->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // Don't allow deleting yourself
        $currentUserId = $this->security->getCurrentUserId($request);
        if ($currentUserId === $user->getId()) {
            return $this->json(['error' => 'Cannot delete your own account'], 400);
        }

        $this->em->remove($user);
        $this->em->flush();

        return new JsonResponse(null, 204);
    }
}
