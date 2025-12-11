<?php
namespace App\Controller;

use App\Controller\Traits\ValidationTrait;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\CreateUserRequest;
use App\Request\UpdateUserRequest;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserManagementController extends AbstractController
{
    use ValidationTrait;
    public function create(Request $request, ManagerRegistry $doctrine, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        // Walidacja DTO
        $dto = $this->mapArrayToDto($data, new CreateUserRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $user = (new User())
            ->setEmail($data['email'])
            ->setName($data['name'])
            ->setRoles($data['roles'] ?? ['ROLE_USER']);

        $group = $data['membershipGroup'] ?? User::GROUP_STANDARD;
        try {
            $user->setMembershipGroup($group);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => 'Unknown membership group'], 400);
        }

        if (isset($data['loanLimit'])) {
            $user->setLoanLimit((int) $data['loanLimit']);
        }

        $this->applyContactData($user, $data);

        if (!empty($data['blocked'])) {
            $reason = array_key_exists('blockedReason', $data) ? (string) $data['blockedReason'] : null;
            $user->block($reason);
        }

        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));

        if (array_key_exists('pendingApproval', $data)) {
            $user->setPendingApproval((bool) $data['pendingApproval']);
        } else {
            $user->setPendingApproval(false);
        }

        if (array_key_exists('verified', $data)) {
            if ((bool) $data['verified']) {
                $user->markVerified();
            } else {
                $user->requireVerification();
            }
        } else {
            $user->markVerified();
        }

        $user->recordPrivacyConsent();

        $em = $doctrine->getManager();
        /** @var EntityManagerInterface $em */
        $conn = $em->getConnection();
        
        $conn->beginTransaction();
        try {
            $em->persist($user);
            $em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            return $this->json(['error' => 'Błąd podczas tworzenia użytkownika'], 500);
        }

        return $this->json($user, 201);
    }

    public function update(string $id, Request $request, UserRepository $repo, ManagerRegistry $doctrine, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        $isAdmin = $security->hasRole($request, 'ROLE_ADMIN');
        $isLibrarian = $security->hasRole($request, 'ROLE_LIBRARIAN');
        $canManage = $isAdmin || $isLibrarian;
        $payload = $security->getJwtPayload($request);
        $isOwner = $payload && isset($payload['sub']) && (int) $payload['sub'] === (int) $id;

        if (!($canManage || $isOwner)) {
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
        
        // Walidacja DTO
        $dto = $this->mapArrayToDto($data, new UpdateUserRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        if (!empty($data['name'])) {
            $user->setName($data['name']);
        }
        if (!empty($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['roles'])) {
            if (!$isAdmin) {
                return $this->json(['error' => 'Forbidden to change roles'], 403);
            }
            $user->setRoles(array_values(array_unique((array) $data['roles'])));
        }

        $this->applyContactData($user, $data);

        if (array_key_exists('pendingApproval', $data)) {
            if (!$isLibrarian) {
                return $this->json(['error' => 'Forbidden to change approval status'], 403);
            }
            $user->setPendingApproval((bool) $data['pendingApproval']);
        }

        if (array_key_exists('verified', $data)) {
            if (!$isLibrarian) {
                return $this->json(['error' => 'Forbidden to change verification status'], 403);
            }
            if ((bool) $data['verified']) {
                $user->markVerified();
            } else {
                $user->requireVerification();
            }
        }

        if (isset($data['membershipGroup'])) {
            if (!$isLibrarian) {
                return $this->json(['error' => 'Forbidden to change membership group'], 403);
            }
            try {
                $user->setMembershipGroup((string) $data['membershipGroup']);
            } catch (\InvalidArgumentException $exception) {
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
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }

        $user = $repo->find((int) $id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

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
            } catch (\InvalidArgumentException $exception) {
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

    private function applyContactData(User $user, array $data): void
    {
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
    }
}
