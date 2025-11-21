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
}
