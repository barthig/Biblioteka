<?php
namespace App\Tests\Functional;

use App\Entity\BookCollection;

class CollectionControllerTest extends ApiTestCase
{
    public function testListCollectionsReturnsData(): void
    {
        $curator = $this->createUser('curator@example.com', ['ROLE_LIBRARIAN']);
        $collection = (new BookCollection())
            ->setName('Top Picks')
            ->setCuratedBy($curator);
        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/collections');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetCollectionReturnsData(): void
    {
        $curator = $this->createUser('curator@example.com', ['ROLE_LIBRARIAN']);
        $collection = (new BookCollection())
            ->setName('Top Picks')
            ->setCuratedBy($curator);
        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/collections/' . $collection->getId());

        $this->assertResponseStatusCodeSame(200);
    }

    public function testCreateCollectionRequiresLibrarian(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->jsonRequest($client, 'POST', '/api/collections', ['name' => 'New Collection']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateCollectionCreatesEntry(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $this->jsonRequest($client, 'POST', '/api/collections', ['name' => 'New Collection']);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testUpdateCollectionRequiresLibrarian(): void
    {
        $curator = $this->createUser('curator@example.com', ['ROLE_LIBRARIAN']);
        $collection = (new BookCollection())
            ->setName('Top Picks')
            ->setCuratedBy($curator);
        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->jsonRequest($client, 'PUT', '/api/collections/' . $collection->getId(), ['name' => 'Updated']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateCollectionUpdatesEntry(): void
    {
        $curator = $this->createUser('curator@example.com', ['ROLE_LIBRARIAN']);
        $collection = (new BookCollection())
            ->setName('Top Picks')
            ->setCuratedBy($curator);
        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($curator);
        $this->jsonRequest($client, 'PUT', '/api/collections/' . $collection->getId(), ['name' => 'Updated']);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testDeleteCollectionRequiresAdmin(): void
    {
        $curator = $this->createUser('curator@example.com', ['ROLE_LIBRARIAN']);
        $collection = (new BookCollection())
            ->setName('Top Picks')
            ->setCuratedBy($curator);
        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($curator);
        $this->sendRequest($client, 'DELETE', '/api/collections/' . $collection->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteCollectionRemovesEntry(): void
    {
        $curator = $this->createUser('curator@example.com', ['ROLE_LIBRARIAN']);
        $collection = (new BookCollection())
            ->setName('Top Picks')
            ->setCuratedBy($curator);
        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);
        $this->sendRequest($client, 'DELETE', '/api/collections/' . $collection->getId());

        $this->assertResponseStatusCodeSame(204);
    }
}
