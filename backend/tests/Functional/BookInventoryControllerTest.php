<?php
namespace App\Tests\Functional;

use App\Entity\BookCopy;

class BookInventoryControllerTest extends ApiTestCase
{
    public function testListCopiesRequiresLibrarian(): void
    {
        $book = $this->createBook('Inventory Book');
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/admin/books/' . $book->getId() . '/copies');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testListCopiesReturnsData(): void
    {
        $book = $this->createBook('Inventory Book');
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'GET', '/api/admin/books/' . $book->getId() . '/copies');

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertArrayHasKey('data', $payload);
    }

    public function testCreateCopyRequiresLibrarian(): void
    {
        $book = $this->createBook('Inventory Book');
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->jsonRequest($client, 'POST', '/api/admin/books/' . $book->getId() . '/copies', [
            'inventoryCode' => 'INV-001'
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateCopyCreatesEntry(): void
    {
        $book = $this->createBook('Inventory Book');
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/admin/books/' . $book->getId() . '/copies', [
            'inventoryCode' => 'INV-002',
            'status' => BookCopy::STATUS_AVAILABLE,
            'accessType' => 'storage'
        ]);

        $this->assertResponseStatusCodeSame(201);
        $copy = $this->entityManager->getRepository(BookCopy::class)->findOneBy(['inventoryCode' => 'INV-002']);
        $this->assertNotNull($copy);
    }

    public function testUpdateCopyRequiresLibrarian(): void
    {
        $book = $this->createBook('Inventory Book');
        $copy = $book->getInventory()->first();
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->jsonRequest($client, 'PUT', '/api/admin/books/' . $book->getId() . '/copies/' . $copy->getId(), [
            'status' => BookCopy::STATUS_WITHDRAWN
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateCopyUpdatesData(): void
    {
        $book = $this->createBook('Inventory Book');
        $copy = $book->getInventory()->first();
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'PUT', '/api/admin/books/' . $book->getId() . '/copies/' . $copy->getId(), [
            'status' => BookCopy::STATUS_WITHDRAWN
        ]);

        $this->assertResponseStatusCodeSame(200);
        $reloaded = $this->entityManager->getRepository(BookCopy::class)->find($copy->getId());
        $this->assertSame(BookCopy::STATUS_WITHDRAWN, $reloaded->getStatus());
    }

    public function testDeleteCopyRequiresLibrarian(): void
    {
        $book = $this->createBook('Inventory Book');
        $copy = $book->getInventory()->first();
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'DELETE', '/api/admin/books/' . $book->getId() . '/copies/' . $copy->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteCopyRemovesEntry(): void
    {
        $book = $this->createBook('Inventory Book');
        $copy = $book->getInventory()->first();
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'DELETE', '/api/admin/books/' . $book->getId() . '/copies/' . $copy->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    public function testFindByBarcodeRequiresLibrarian(): void
    {
        $book = $this->createBook('Inventory Book');
        $copy = $book->getInventory()->first();
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/admin/copies/barcode/' . $copy->getInventoryCode());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testFindByBarcodeReturnsCopy(): void
    {
        $book = $this->createBook('Inventory Book');
        $copy = $book->getInventory()->first();
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'GET', '/api/admin/copies/barcode/' . $copy->getInventoryCode());

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertSame($copy->getInventoryCode(), $payload['inventoryCode']);
    }
}
