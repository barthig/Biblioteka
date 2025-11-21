<?php
namespace App\Tests\Functional;

class AccountControllerTest extends ApiTestCase
{
    public function testMeReturnsNewsletterFlag(): void
    {
        $user = $this->createUser('account-me@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->sendRequest($client, 'GET', '/api/me');
        $this->assertResponseStatusCodeSame(200);

        $data = $this->getJsonResponse($client);
        $this->assertArrayHasKey('newsletterSubscribed', $data);
        $this->assertTrue($data['newsletterSubscribed']);
    }

    public function testUpdateAllowsTogglingNewsletterPreference(): void
    {
        $user = $this->createUser('account-toggle@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->jsonRequest($client, 'PUT', '/api/me', ['newsletterSubscribed' => false]);
        $this->assertResponseStatusCodeSame(200);

        $data = $this->getJsonResponse($client);
        $this->assertFalse($data['newsletterSubscribed']);

        $reloaded = $this->entityManager->getRepository(\App\Entity\User::class)->find($user->getId());
        $this->assertNotNull($reloaded);
        $this->assertFalse($reloaded->isNewsletterSubscribed());
    }
}
