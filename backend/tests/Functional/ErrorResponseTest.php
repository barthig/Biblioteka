<?php

namespace App\Tests\Functional;

class ErrorResponseTest extends ApiTestCase
{
    public function testNotFoundReturnsJsonMessage(): void
    {
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/books/999999');

        $this->assertResponseStatusCodeSame(404);

        $response = $client->getResponse();
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));

        $data = $this->getJsonResponse($client);
        $this->assertArrayHasKey('message', $data);
        $this->assertNotSame('', (string) $data['message']);
    }

    public function testUnauthorizedReturnsJsonMessage(): void
    {
        $client = $this->createClientWithoutSecret();
        $this->sendRequest($client, 'GET', '/api/loans');

        $this->assertJsonMessageResponse($client, 401);
    }

    public function testBadRequestReturnsJsonMessage(): void
    {
        $client = $this->createApiClient();
        $this->jsonRequest($client, 'POST', '/api/auth/login', []);

        $this->assertJsonMessageResponse($client, 400);
    }

    public function testForbiddenReturnsJsonMessage(): void
    {
        $user = $this->createUser('reader@example.com', ['ROLE_USER']);
        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'GET', '/api/notifications');

        $this->assertJsonMessageResponse($client, 403);
    }

    public function testConflictReturnsJsonMessage(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);

        $role = new \App\Entity\StaffRole();
        $role->setName('Circulation');
        $role->setRoleKey('ROLE_CIRCULATION');
        $role->setModules(['loans']);
        $this->entityManager->persist($role);
        $this->entityManager->flush();

        $this->jsonRequest($client, 'POST', '/api/admin/system/roles', [
            'name' => 'Circulation',
            'roleKey' => 'ROLE_CIRCULATION',
            'modules' => ['loans'],
        ]);

        $this->assertJsonMessageResponse($client, 409);
    }

    public function testGoneReturnsJsonMessage(): void
    {
        $admin = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($admin);

        $book = $this->createBook('Missing Asset Book');
        $asset = new \App\Entity\BookDigitalAsset();
        $asset->setBook($book);
        $asset->setLabel('Digital file');
        $asset->setOriginalFilename('missing.pdf');
        $asset->setMimeType('application/pdf');
        $asset->setSize(123);
        $asset->setStorageName('missing-' . uniqid());
        $this->entityManager->persist($asset);
        $this->entityManager->flush();

        $this->sendRequest($client, 'GET', sprintf('/api/admin/books/%d/assets/%d', $book->getId(), $asset->getId()));

        $this->assertJsonMessageResponse($client, 410);
    }

    public function testUnprocessableEntityReturnsJsonMessage(): void
    {
        $admin = $this->createUser('librarian2@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($admin);

        $this->jsonRequest($client, 'POST', '/api/notifications/test', [
            'channel' => 'fax',
            'target' => 'receiver',
        ]);

        $this->assertJsonMessageResponse($client, 422);
    }

    public function testTooManyRequestsReturnsJsonMessage(): void
    {
        $client = $this->createApiClient();
        $payload = ['email' => 'missing@example.com', 'password' => 'bad'];
        $server = ['REMOTE_ADDR' => '127.0.0.77'];

        for ($i = 0; $i < 6; $i++) {
            $this->jsonRequest($client, 'POST', '/api/auth/login', $payload, $server);
        }

        $this->assertJsonMessageResponse($client, 429);
    }

    public function testInternalServerErrorReturnsJsonMessage(): void
    {
        $admin = $this->createUser('librarian3@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($admin);
        $this->sendRequest($client, 'GET', '/api/reports/export?format=pdf&simulateFailure=1');

        $this->assertJsonMessageResponse($client, 500);
    }

    public function testServiceUnavailableReturnsJsonMessage(): void
    {
        $admin = $this->createUser('librarian4@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($admin);
        $this->sendRequest($client, 'GET', '/api/notifications?serviceDown=1');

        $this->assertJsonMessageResponse($client, 503);
    }

    private function assertJsonMessageResponse($client, int $status): void
    {
        $this->assertResponseStatusCodeSame($status);

        $response = $client->getResponse();
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));

        $data = $this->getJsonResponse($client);
        $this->assertArrayHasKey('message', $data);
        $this->assertNotSame('', (string) $data['message']);
    }
}
