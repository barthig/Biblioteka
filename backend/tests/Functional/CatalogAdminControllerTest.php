<?php
namespace App\Tests\Functional;

class CatalogAdminControllerTest extends ApiTestCase
{
    public function testExportRequiresLibrarian(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/admin/catalog/export');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testImportRequiresLibrarian(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->jsonRequest($client, 'POST', '/api/admin/catalog/import', ['items' => []]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testImportValidatesPayload(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $this->jsonRequest($client, 'POST', '/api/admin/catalog/import', ['invalid' => []]);

        $this->assertResponseStatusCodeSame(400);
    }
}
