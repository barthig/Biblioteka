<?php
namespace App\Tests\Functional;

use App\Entity\Author;

class AuthorControllerTest extends ApiTestCase
{
    public function testListAuthorsReturnsData(): void
    {
        $this->createAuthor('Author One');
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/authors');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetAuthorReturnsData(): void
    {
        $author = $this->createAuthor('Author Two');
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/authors/' . $author->getId());

        $this->assertResponseStatusCodeSame(200);
    }

    public function testCreateAuthorRequiresLibrarian(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->jsonRequest($client, 'POST', '/api/authors', ['name' => 'New Author']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateAuthorCreatesEntry(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $this->jsonRequest($client, 'POST', '/api/authors', ['name' => 'New Author']);

        $this->assertResponseStatusCodeSame(201);
        $author = $this->entityManager->getRepository(Author::class)->findOneBy(['name' => 'New Author']);
        $this->assertNotNull($author);
    }

    public function testUpdateAuthorRequiresLibrarian(): void
    {
        $author = $this->createAuthor('Old Author');
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->jsonRequest($client, 'PUT', '/api/authors/' . $author->getId(), ['name' => 'Updated Author']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateAuthorUpdatesEntry(): void
    {
        $author = $this->createAuthor('Old Author');
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $this->jsonRequest($client, 'PUT', '/api/authors/' . $author->getId(), ['name' => 'Updated Author']);

        $this->assertResponseStatusCodeSame(200);
        $updated = $this->entityManager->getRepository(Author::class)->find($author->getId());
        $this->assertSame('Updated Author', $updated->getName());
    }

    public function testDeleteAuthorRequiresLibrarian(): void
    {
        $author = $this->createAuthor('To Delete');
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'DELETE', '/api/authors/' . $author->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteAuthorRemovesEntry(): void
    {
        $author = $this->createAuthor('To Delete');
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'DELETE', '/api/authors/' . $author->getId());

        $this->assertResponseStatusCodeSame(204);
    }
}
