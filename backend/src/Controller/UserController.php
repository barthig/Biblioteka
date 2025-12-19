<?php
namespace App\Controller;

use App\Application\Query\User\GetUserDetailsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    use HandleTrait;
    use ExceptionHandlingTrait;

    public function __construct(
        private SecurityService $security,
        private MessageBusInterface $messageBus
    ) {
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    public function list(UserRepository $repo, Request $request): JsonResponse
    {
        // Only librarians can list all users
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $users = array_values(array_filter(
            $repo->findAll(),
            static fn(User $user) => !in_array('ROLE_SYSTEM', $user->getRoles(), true)
        ));
        return $this->json($users, 200, [], ['groups' => ['user:read']]);
    }

    public function getUserById(string $id, UserRepository $repo, Request $request): JsonResponse
    {
        // validate id — should be positive integer
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }
        
        $userId = (int)$id;
        $currentUserId = $this->security->getCurrentUserId($request);
        
        // Users can only see their own profile unless they're librarians
        if ($currentUserId !== $userId && !$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $user = $repo->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }
        
        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    public function search(UserRepository $repo, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $query = $request->query->get('q', '');
        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $users = $repo->searchUsers($query);
        return $this->json($users, 200, [], ['groups' => ['user:read']]);
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    public function getUserDetails(string $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }

        try {
            $result = $this->handle(new GetUserDetailsQuery((int)$id));
            
            return $this->json($result, 200, [], [
                'groups' => ['user:read', 'loan:read', 'fine:read']
            ]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[IsGranted('ROLE_ADMIN')]
    public function update(string $id, UserRepository $repo, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $userId = (int)$id;
        $user = $repo->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }
        
        if (isset($data['active']) && is_bool($data['active'])) {
            $user->setActive($data['active']);
        }

        $repo->save($user, true);
        
        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id, UserRepository $repo, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $userId = (int)$id;
        $currentUserId = $this->security->getCurrentUserId($request);
        
        // Prevent admin from deleting themselves
        if ($userId === $currentUserId) {
            return $this->json(['error' => 'Cannot delete your own account'], 400);
        }

        $user = $repo->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $repo->remove($user, true);

        return new JsonResponse(null, 204);
    }
}
