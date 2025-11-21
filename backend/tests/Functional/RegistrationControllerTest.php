<?php
namespace App\Tests\Functional;

use App\Entity\RegistrationToken;
use App\Entity\User;

class RegistrationControllerTest extends ApiTestCase
{
    public function testRegisterAndVerify(): void
    {
        $client = $this->createClientWithoutSecret();

        $payload = [
            'email' => 'reg@example.com',
            'name' => 'Reg User',
            'password' => 'StrongPass1',
            'privacyConsent' => true,
        ];

        $this->jsonRequest($client, 'POST', '/api/auth/register', $payload);
        $this->assertResponseStatusCodeSame(201);

        $data = $this->getJsonResponse($client);
        $this->assertSame('pending_verification', $data['status']);
        $this->assertArrayHasKey('userId', $data);

        $user = $this->entityManager->getRepository(User::class)->find($data['userId']);
        $this->assertNotNull($user);

        $token = $this->entityManager->getRepository(RegistrationToken::class)->findOneBy(['user' => $user]);
        $this->assertNotNull($token, 'Registration token should be created');

        $this->sendRequest($client, 'GET', '/api/auth/verify/' . $token->getToken());
        $this->assertResponseStatusCodeSame(200);

        $body = $this->getJsonResponse($client);
        $this->assertSame('account_verified', $body['status']);
        $this->assertSame($user->getId(), $body['userId']);
    }

    public function testRegisterDefaultsNewsletterSubscription(): void
    {
        $client = $this->createClientWithoutSecret();

        $this->jsonRequest($client, 'POST', '/api/auth/register', [
            'email' => 'newsletter-default@example.com',
            'name' => 'Newsletter Default',
            'password' => 'StrongPass1',
            'privacyConsent' => true,
        ]);

        $this->assertResponseStatusCodeSame(201);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'newsletter-default@example.com']);
        self::assertNotNull($user);
        self::assertTrue($user->isNewsletterSubscribed(), 'Newsletter flag should default to TRUE');
    }

    public function testRegisterAllowsNewsletterOptOut(): void
    {
        $client = $this->createClientWithoutSecret();

        $this->jsonRequest($client, 'POST', '/api/auth/register', [
            'email' => 'newsletter-optout@example.com',
            'name' => 'Newsletter OptOut',
            'password' => 'StrongPass1',
            'privacyConsent' => true,
            'newsletterSubscribed' => false,
        ]);

        $this->assertResponseStatusCodeSame(201);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'newsletter-optout@example.com']);
        self::assertNotNull($user);
        self::assertFalse($user->isNewsletterSubscribed(), 'Explicit opt-out should persist');
    }

    public function testRegisterDuplicateEmail(): void
    {
        $this->createUser('dup@example.com');

        $client = $this->createClientWithoutSecret();
        $this->jsonRequest($client, 'POST', '/api/auth/register', [
            'email' => 'dup@example.com',
            'name' => 'Dup',
            'password' => 'StrongPass1',
            'privacyConsent' => true,
        ]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testRegisterRequiresPrivacyConsent(): void
    {
        $client = $this->createClientWithoutSecret();

        $this->jsonRequest($client, 'POST', '/api/auth/register', [
            'email' => 'consent@example.com',
            'name' => 'No Consent',
            'password' => 'StrongPass1',
            'privacyConsent' => false,
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterRejectsWeakPassword(): void
    {
        $client = $this->createClientWithoutSecret();

        $this->jsonRequest($client, 'POST', '/api/auth/register', [
            'email' => 'weakpass@example.com',
            'name' => 'Weak Pass',
            'password' => 'weakpass',
            'privacyConsent' => true,
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testVerifyWithInvalidTokenReturns404(): void
    {
        $client = $this->createClientWithoutSecret();

        $this->sendRequest($client, 'GET', '/api/auth/verify/deadbeef');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testVerifyWithExpiredTokenReturns410(): void
    {
        $user = $this->createUser('expired@example.com');
        $user->requireVerification();

        $token = new RegistrationToken($user, bin2hex(random_bytes(8)), new \DateTimeImmutable('-1 hour'));

        $this->entityManager->persist($user);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $client = $this->createClientWithoutSecret();

        $this->sendRequest($client, 'GET', '/api/auth/verify/' . $token->getToken());

        $this->assertResponseStatusCodeSame(410);
    }

    public function testVerifyWithConsumedTokenReturns410(): void
    {
        $user = $this->createUser('consumed@example.com');
        $user->requireVerification();

        $token = new RegistrationToken($user, bin2hex(random_bytes(8)), new \DateTimeImmutable('+1 hour'));
        $token->markUsed();

        $this->entityManager->persist($user);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $client = $this->createClientWithoutSecret();

        $this->sendRequest($client, 'GET', '/api/auth/verify/' . $token->getToken());

        $this->assertResponseStatusCodeSame(410);
    }
}
