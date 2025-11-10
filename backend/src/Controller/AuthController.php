<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\JwtService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AuthController extends AbstractController
{
    public function login(Request $request, UserRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $email = $data['email'] ?? null;
        if (!$email) return $this->json(['error' => 'Missing email'], 400);

        $password = $data['password'] ?? null;
        if (!$password) return $this->json(['error' => 'Missing password'], 400);

        $user = $repo->findOneBy(['email' => $email]);
        if (!$user) return $this->json(['error' => 'User not found'], 404);

        // verify password
        if (!password_verify($password, $user->getPassword())) {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }

        $token = JwtService::createToken(['sub' => $user->getId(), 'roles' => $user->getRoles()]);
        return $this->json(['token' => $token], 200);
    }

    public function register(Request $request, ManagerRegistry $doctrine, UserRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];

        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Podaj poprawny adres e-mail'], 400);
        }

        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        if ($name === '') {
            return $this->json(['error' => 'Imię i nazwisko są wymagane'], 400);
        }

        $password = (string) ($data['password'] ?? '');
        if (strlen($password) < 8) {
            return $this->json(['error' => 'Hasło musi mieć co najmniej 8 znaków'], 400);
        }

        $existing = $repo->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->json(['error' => 'Użytkownik o podanym adresie e-mail już istnieje'], 409);
        }

        $user = (new User())
            ->setEmail($email)
            ->setName($name)
            ->setRoles(['ROLE_USER'])
            ->setPassword(password_hash($password, PASSWORD_BCRYPT));

        if (isset($data['phoneNumber'])) {
            $phone = trim((string) $data['phoneNumber']);
            $user->setPhoneNumber($phone !== '' ? $phone : null);
        }

        if (isset($data['addressLine'])) {
            $address = trim((string) $data['addressLine']);
            $user->setAddressLine($address !== '' ? $address : null);
        }

        if (isset($data['city'])) {
            $city = trim((string) $data['city']);
            $user->setCity($city !== '' ? $city : null);
        }

        if (isset($data['postalCode'])) {
            $postal = trim((string) $data['postalCode']);
            $user->setPostalCode($postal !== '' ? $postal : null);
        }

        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();

        $token = JwtService::createToken(['sub' => $user->getId(), 'roles' => $user->getRoles()]);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
                'phoneNumber' => $user->getPhoneNumber(),
                'addressLine' => $user->getAddressLine(),
                'city' => $user->getCity(),
                'postalCode' => $user->getPostalCode(),
            ],
        ], 201);
    }
}
