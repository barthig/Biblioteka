<?php
namespace App\Tests\Functional;

class AdminVectorControllerTest extends ApiTestCase
{
    public function testStatsRequireAdmin(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/admin/books/embeddings/stats');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testReindexRequiresAdmin(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'POST', '/api/admin/books/embeddings/reindex');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testStatsReturnDataForAdmin(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);
        $this->sendRequest($client, 'GET', '/api/admin/books/embeddings/stats');

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertArrayHasKey('totalBooks', $payload);
    }
}
