<?php
namespace App\Tests\Functional;

use App\Entity\Book;

class BookControllerTest extends ApiTestCase
{
    public function testListRequiresAuthentication(): void
    {
    $client = $this->createClientWithoutSecret();
    $this->sendRequest($client, 'GET', '/api/books');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testListReturnsBooks(): void
    {
        $book = $this->createBook('Clean Code', 'Robert C. Martin', 2);

    $client = $this->createApiClient();
    $this->sendRequest($client, 'GET', '/api/books');

        $this->assertResponseStatusCodeSame(200);

        $data = $this->getJsonResponse($client);
        $this->assertCount(1, $data);
        $this->assertSame($book->getTitle(), $data[0]['title']);
    }

    public function testCreateRequiresLibrarianRole(): void
    {
        $user = $this->createUser('reader@example.com');

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/books', [
            'title' => 'Domain-Driven Design',
            'author' => 'Eric Evans',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateBookSucceedsForLibrarian(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);

        $client = $this->createAuthenticatedClient($librarian);
        $this->jsonRequest($client, 'POST', '/api/books', [
            'title' => 'Domain-Driven Design',
            'author' => 'Eric Evans',
            'copies' => 4,
        ]);

        $this->assertResponseStatusCodeSame(201);

        $data = $this->getJsonResponse($client);
        $this->assertSame('Domain-Driven Design', $data['title']);
        $this->assertSame('Eric Evans', $data['author']);
        $this->assertSame(4, $data['copies']);
    }

    public function testCreateBookValidatesPayload(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);

        $client = $this->createAuthenticatedClient($librarian);
        $this->jsonRequest($client, 'POST', '/api/books', [
            'title' => '',
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateBook(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $book = $this->createBook('Legacy Title', 'Unknown Author', 1);

        $client = $this->createAuthenticatedClient($librarian);
        $this->jsonRequest($client, 'PUT', '/api/books/' . $book->getId(), [
            'title' => 'Refactored Title',
            'copies' => 5,
        ]);

        $this->assertResponseStatusCodeSame(200);

        $updated = $this->entityManager->getRepository(Book::class)->find($book->getId());
        self::assertNotNull($updated);
        self::assertSame('Refactored Title', $updated->getTitle());
        self::assertSame(5, $updated->getCopies());
    }

    public function testDeleteBook(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $book = $this->createBook('Disposable Book', 'Author', 1);

    $client = $this->createAuthenticatedClient($librarian);
    $this->sendRequest($client, 'DELETE', '/api/books/' . $book->getId());

        $this->assertResponseStatusCodeSame(204);

        $deleted = $this->entityManager->getRepository(Book::class)->find($book->getId());
        self::assertNull($deleted);
    }
}
