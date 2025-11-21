<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends AbstractController
{
    public function me(Request $request, UserRepository $repo, SecurityService $security): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request, $repo, $security);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        return $this->json($this->formatUser($user));
    }

    public function update(Request $request, ManagerRegistry $doctrine, UserRepository $repo, SecurityService $security): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request, $repo, $security);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $data = json_decode($request->getContent(), true) ?: [];

        if (isset($data['email'])) {
            $email = trim((string) $data['email']);
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->json(['error' => 'Podaj poprawny adres e-mail'], 400);
            }
            $existing = $repo->findOneBy(['email' => $email]);
            if ($existing && $existing->getId() !== $user->getId()) {
                return $this->json(['error' => 'Adres e-mail jest już zajęty'], 409);
            }
            $user->setEmail($email);
        }

        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                return $this->json(['error' => 'Imię i nazwisko nie mogą być puste'], 400);
            }
            $user->setName($name);
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

        if (array_key_exists('newsletterSubscribed', $data)) {
            $user->setNewsletterSubscribed($this->normalizeBoolean($data['newsletterSubscribed']));
        }

        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();

        return $this->json($this->formatUser($user));
    }

    public function changePassword(Request $request, ManagerRegistry $doctrine, UserRepository $repo, SecurityService $security): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request, $repo, $security);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $currentPassword = (string) ($data['currentPassword'] ?? '');
        $newPassword = (string) ($data['newPassword'] ?? '');
        $confirmPassword = (string) ($data['confirmPassword'] ?? $newPassword);

        if ($currentPassword === '' || $newPassword === '') {
            return $this->json(['error' => 'Podaj aktualne i nowe hasło'], 400);
        }

        if (!password_verify($currentPassword, $user->getPassword())) {
            return $this->json(['error' => 'Aktualne hasło jest niepoprawne'], 400);
        }

        if (strlen($newPassword) < 8) {
            return $this->json(['error' => 'Nowe hasło musi mieć co najmniej 8 znaków'], 400);
        }

        if ($currentPassword === $newPassword) {
            return $this->json(['error' => 'Nowe hasło musi się różnić od poprzedniego'], 400);
        }

        if ($newPassword !== $confirmPassword) {
            return $this->json(['error' => 'Potwierdzenie hasła nie zgadza się z nowym hasłem'], 400);
        }

        $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Hasło zostało zaktualizowane']);
    }

    private function resolveAuthenticatedUser(Request $request, UserRepository $repo, SecurityService $security): JsonResponse|User
    {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $repo->find((int) $payload['sub']);
        if (!$user) {
            return $this->json(['error' => 'Użytkownik nie istnieje'], 404);
        }

        return $user;
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'phoneNumber' => $user->getPhoneNumber(),
            'addressLine' => $user->getAddressLine(),
            'city' => $user->getCity(),
            'postalCode' => $user->getPostalCode(),
            'newsletterSubscribed' => $user->isNewsletterSubscribed(),
        ];
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
