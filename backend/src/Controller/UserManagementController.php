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
        if (empty($data['email']) || empty($data['name']) || empty($data['password'])) {
            return $this->json(['error' => 'Missing email, name or password'], 400);
        }

        $user = new User();
        $user->setEmail($data['email'])->setName($data['name'])->setRoles($data['roles'] ?? ['ROLE_USER']);
        $group = $data['membershipGroup'] ?? User::GROUP_STANDARD;
        try {
            $user->setMembershipGroup($group);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Unknown membership group'], 400);
        }
        if (isset($data['loanLimit'])) {
            $user->setLoanLimit((int)$data['loanLimit']);
        }

        if (!empty($data['blocked'])) {
            $reason = isset($data['blockedReason']) ? (string) $data['blockedReason'] : null;
            $user->block($reason);
        }
        // hash and set password
        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);
        $user->setPassword($hashed);
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
        if (isset($data['roles'])) {
            if (!$isLibrarian) {
                return $this->json(['error' => 'Forbidden to change roles'], 403);
            }
            $user->setRoles((array)$data['roles']);
        }
        if (array_key_exists('phoneNumber', $data)) {
            $phone = trim((string) $data['phoneNumber']);
            $user->setPhoneNumber($phone !== '' ? $phone : null);
        }
        if (array_key_exists('addressLine', $data)) {
            $address = trim((string) $data['addressLine']);
            $user->setAddressLine($address !== '' ? $address : null);
        }
        if (array_key_exists('city', $data)) {
            $city = trim((string) $data['city']);
            $user->setCity($city !== '' ? $city : null);
        }
        if (array_key_exists('postalCode', $data)) {
            $postal = trim((string) $data['postalCode']);
            $user->setPostalCode($postal !== '' ? $postal : null);
        }
        if (isset($data['membershipGroup'])) {
            if (!$isLibrarian) {
                return $this->json(['error' => 'Forbidden to change membership group'], 403);
            }
            try {
                $user->setMembershipGroup((string) $data['membershipGroup']);
            } catch (\InvalidArgumentException $e) {
                return $this->json(['error' => 'Unknown membership group'], 400);
            }
        }
        if (isset($data['loanLimit'])) {
            if (!$isLibrarian) {
                return $this->json(['error' => 'Forbidden to change loan limit'], 403);
            }
            $user->setLoanLimit((int) $data['loanLimit']);
        }
        if (isset($data['blocked'])) {
            if (!$isLibrarian) {
                return $this->json(['error' => 'Forbidden to change block status'], 403);
            }
            $reason = array_key_exists('blockedReason', $data) ? (string) $data['blockedReason'] : null;
            if ($data['blocked']) {
                $user->block($reason);
            } else {
                $user->unblock();
            }
        }
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

    public function block(string $id, Request $request, UserRepository $repo, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }

        $user = $repo->find((int) $id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $reason = isset($data['reason']) ? (string) $data['reason'] : null;
        $user->block($reason);

        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();

        return $this->json($user, 200);
    }

    public function unblock(string $id, Request $request, UserRepository $repo, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }

        $user = $repo->find((int) $id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $user->unblock();
        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();

        return $this->json($user, 200);
    }

    public function updatePermissions(string $id, Request $request, UserRepository $repo, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }

        $user = $repo->find((int) $id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        if (!isset($data['membershipGroup']) && !isset($data['loanLimit'])) {
            return $this->json(['error' => 'No changes requested'], 400);
        }

        if (isset($data['membershipGroup'])) {
            try {
                $user->setMembershipGroup((string) $data['membershipGroup']);
            } catch (\InvalidArgumentException $e) {
                return $this->json(['error' => 'Unknown membership group'], 400);
            }
        }

        if (isset($data['loanLimit'])) {
            $user->setLoanLimit((int) $data['loanLimit']);
        }

        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();

        return $this->json($user, 200);
    }
}
