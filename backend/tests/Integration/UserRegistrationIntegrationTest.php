<?php

namespace App\Tests\Integration;

use App\Entity\RegistrationToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserRegistrationIntegrationTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->setAppEnvironment('test');
    }

    protected function tearDown(): void
    {
        $this->setAppEnvironment('test');
        parent::tearDown();
    }

    private function setAppEnvironment(string $value): void
    {
        putenv('APP_ENV=' . $value);
        $_ENV['APP_ENV'] = $value;
        $_SERVER['APP_ENV'] = $value;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    private function getApiHeaders(bool $includeSecret = true): array
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];
        if ($includeSecret) {
            $headers['HTTP_X_API_SECRET'] = $_ENV['API_SECRET'] ?? 'test-secret';
        }

        return $headers;
    }

    private function decodeLastResponse(): array
    {
        $content = $this->client->getResponse()->getContent();
        if ($content === null || $content === false) {
            return [];
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testCompleteUserRegistrationAndVerification(): void
    {
        $email = sprintf('newuser_%s@example.com', bin2hex(random_bytes(6)));
        $payload = [
            'email' => $email,
            'name' => 'Jan Testowy',
            'password' => 'SecurePass123!',
            'privacyConsent' => true,
        ];

        $this->client->request('POST', '/api/auth/register', [], [], $this->getApiHeaders(), json_encode($payload));
        $this->assertResponseStatusCodeSame(201);

        $registrationResponse = $this->decodeLastResponse();
        $this->assertSame('pending_verification', $registrationResponse['status']);
        $this->assertArrayHasKey('userId', $registrationResponse);

        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find($registrationResponse['userId']);
        $this->assertNotNull($user, 'User should exist after registration');

        $token = $em->getRepository(RegistrationToken::class)->findOneBy(['user' => $user]);
        $this->assertNotNull($token, 'Registration token should be generated');

        $this->setAppEnvironment('prod');

        $this->client->request('POST', '/api/auth/login', [], [], $this->getApiHeaders(), json_encode([
            'email' => $email,
            'password' => $payload['password'],
        ]));

        $this->assertResponseStatusCodeSame(403);
        $loginError = $this->decodeLastResponse();
        $this->assertSame('FORBIDDEN', $loginError['error']['code']);
        $this->assertSame('Access denied', $loginError['error']['message']);

        $this->client->request('GET', '/api/auth/verify/' . $token->getToken(), [], [], $this->getApiHeaders());
        $this->assertResponseStatusCodeSame(200);

        $verificationResponse = $this->decodeLastResponse();
        $this->assertSame('account_verified', $verificationResponse['status']);
        $this->assertSame($user->getId(), $verificationResponse['userId']);

        $this->client->request('POST', '/api/auth/login', [], [], $this->getApiHeaders(), json_encode([
            'email' => $email,
            'password' => $payload['password'],
        ]));

        $this->assertResponseStatusCodeSame(200);
        $postVerifyLogin = $this->decodeLastResponse();
        $this->assertArrayHasKey('token', $postVerifyLogin);
        $this->assertArrayHasKey('refreshToken', $postVerifyLogin);
    }

    public function testDuplicateEmailRegistrationFails(): void
    {
        $email = sprintf('dupuser_%s@example.com', bin2hex(random_bytes(6)));

        $user = new User();
        $user->setEmail($email)
            ->setName('Existing User')
            ->setRoles(['ROLE_USER'])
            ->setPassword(password_hash('ExistingPass1', PASSWORD_BCRYPT))
            ->markVerified();

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        $this->client->request('POST', '/api/auth/register', [], [], $this->getApiHeaders(), json_encode([
            'email' => $email,
            'name' => 'Nowy Członek',
            'password' => 'SecurePass123!',
            'privacyConsent' => true,
        ]));

        $this->assertResponseStatusCodeSame(409);
    }

    public function testWeakPasswordValidation(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], $this->getApiHeaders(), json_encode([
            'email' => sprintf('weakpass_%s@example.com', bin2hex(random_bytes(6))),
            'name' => 'Słabe Hasło',
            'password' => '123',
            'privacyConsent' => true,
        ]));

        $this->assertResponseStatusCodeSame(400);
        $response = $this->decodeLastResponse();

        $this->assertSame('VALIDATION_FAILED', $response['error']['code']);
        $this->assertSame(400, $response['error']['statusCode']);
        $this->assertArrayHasKey('password', $response['error']['details']);
        $passwordDetail = $response['error']['details']['password'];
        $detailString = is_array($passwordDetail) ? implode(' ', $passwordDetail) : (string) $passwordDetail;
        $this->assertStringContainsString('Hasło', $detailString);
    }
}
