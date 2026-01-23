<?php
namespace App\Service;

use App\Entity\RegistrationToken;
use App\Entity\User;
use App\Repository\RegistrationTokenRepository;
use App\Repository\UserRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class RegistrationService
{
    private const DEFAULT_TOKEN_TTL = 172800; // 48 hours

    private bool $requireApproval = false;
    private int $tokenTtl = self::DEFAULT_TOKEN_TTL;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $users,
        private RegistrationTokenRepository $tokens,
        private OpenAIEmbeddingService $embeddingService
    ) {

        $requireApproval = getenv('REGISTRATION_REQUIRE_APPROVAL') ?: ($_ENV['REGISTRATION_REQUIRE_APPROVAL'] ?? 'false');
        $this->requireApproval = $this->toBool($requireApproval);

        $ttlValue = getenv('REGISTRATION_TOKEN_TTL') ?: ($_ENV['REGISTRATION_TOKEN_TTL'] ?? null);
        $this->tokenTtl = $this->normalizeTtl($ttlValue);
    }

    /**
     * @param array{email?: string, name?: string, password?: string, privacyConsent?: bool|string, phoneNumber?: string|null, addressLine?: string|null, city?: string|null, postalCode?: string|null, newsletterSubscribed?: bool|string|null, tastePrompt?: string|null} $data
     */
    public function register(array $data): RegistrationToken
    {
        $email = isset($data['email']) ? strtolower(trim((string) $data['email'])) : '';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw RegistrationException::validation('Podaj poprawny adres e-mail.');
        }

        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        if ($name === '') {
            throw RegistrationException::validation('Imie i nazwisko sa wymagane.');
        }

        $password = (string) ($data['password'] ?? '');
        $this->assertPasswordStrength($password);


        // Email musi być unikalny
        if ($this->users->findOneBy(['email' => $email])) {
            throw RegistrationException::validation('Konto z takim adresem e-mail już istnieje.', 409);
        }

        // Numer telefonu musi być unikalny (jeśli podany)
        if (isset($data['phoneNumber']) && $data['phoneNumber'] !== '') {
            $phone = trim((string) $data['phoneNumber']);
            if ($phone !== '' && $this->users->findOneBy(['phoneNumber' => $phone])) {
                throw RegistrationException::validation('Konto z takim numerem telefonu już istnieje.', 409);
            }
        }

        // PESEL musi być unikalny (jeśli podany)
        if (isset($data['pesel']) && $data['pesel'] !== '') {
            $pesel = trim((string) $data['pesel']);
            if ($pesel !== '' && $this->users->findOneBy(['pesel' => $pesel])) {
                throw RegistrationException::validation('Konto z takim numerem PESEL już istnieje.', 409);
            }
        }

        $consentValue = $data['privacyConsent'] ?? null;
        if (!$this->toBool($consentValue)) {
            throw RegistrationException::validation('Musisz wyrazic zgode na przetwarzanie danych osobowych.');
        }

        $user = (new User())
            ->setEmail($email)
            ->setName($name)
            ->setRoles(['ROLE_USER'])
            ->setPendingApproval($this->requireApproval)
            ->requireVerification()
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

        $newsletterPref = array_key_exists('newsletterSubscribed', $data)
            ? $this->toBool($data['newsletterSubscribed'])
            : true;
        $user->setNewsletterSubscribed($newsletterPref);

        $user->recordPrivacyConsent();

        $this->entityManager->persist($user);

        $tastePrompt = isset($data['tastePrompt']) ? trim((string) $data['tastePrompt']) : '';
        if ($tastePrompt !== '') {
            try {
                $embedding = $this->embeddingService->getVector($tastePrompt);
                $user->setTasteEmbedding($embedding);
            } catch (\Throwable $error) {
                error_log('RegistrationService: taste embedding failed: ' . $error->getMessage());
            }
        }

        $tokenValue = bin2hex(random_bytes(32));
        $expiresAt = (new DateTimeImmutable())->add(new DateInterval('PT' . $this->tokenTtl . 'S'));
        $token = new RegistrationToken($user, $tokenValue, $expiresAt);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    public function verify(string $tokenValue): User
    {
        $tokenValue = trim($tokenValue);
        if ($tokenValue === '') {
            throw RegistrationException::validation('Brak tokenu weryfikacyjnego.');
        }

        $token = $this->tokens->findActiveByToken($tokenValue);
        if (!$token) {
            throw RegistrationException::validation('Nieprawidlowy token weryfikacyjny.', 404);
        }

        if ($token->isExpired()) {
            throw RegistrationException::validation('Token wygasl. Popros o nowy link aktywacyjny.', 410);
        }

        if ($token->isConsumed()) {
            throw RegistrationException::validation('Token zostal juz wykorzystany.', 410);
        }

        $user = $token->getUser();
        if (!$user->isVerified()) {
            $user->markVerified();
        }
        $token->markUsed();

        $this->entityManager->persist($user);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $user;
    }

    private function assertPasswordStrength(string $password): void
    {
        if (strlen($password) < 8) {
            throw RegistrationException::validation('Haslo musi miec co najmniej 8 znakow.');
        }

        if (!preg_match('/(?=.*[a-zA-Z])(?=.*\d)/', $password)) {
            throw RegistrationException::validation('Haslo musi zawierac litery oraz co najmniej jedna cyfre.');
        }
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return false;
    }

    private function normalizeTtl(mixed $value): int
    {
        if ($value === null || $value === '') {
            return self::DEFAULT_TOKEN_TTL;
        }

        if (is_numeric($value)) {
            $seconds = (int) $value;
            return $seconds > 0 ? $seconds : self::DEFAULT_TOKEN_TTL;
        }

        return self::DEFAULT_TOKEN_TTL;
    }
}
