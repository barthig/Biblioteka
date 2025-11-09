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
        $author = $this->createAuthor('Robert C. Martin');
        $category = $this->createCategory('Software Engineering');
        $book = $this->createBook('Clean Code', $author, 2, [$category], 3);

        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/books');

        $this->assertResponseStatusCodeSame(200);

        $data = $this->getJsonResponse($client);
        $this->assertCount(1, $data);
        $this->assertSame($book->getTitle(), $data[0]['title']);
        $this->assertSame('Robert C. Martin', $data[0]['author']['name']);
        $this->assertNotEmpty($data[0]['categories']);
    }

    public function testCreateRequiresLibrarianRole(): void
    {
        $user = $this->createUser('reader@example.com');
        $author = $this->createAuthor('Eric Evans');
        $category = $this->createCategory('Design');

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/books', [
            'title' => 'Domain-Driven Design',
            'authorId' => $author->getId(),
            'categoryIds' => [$category->getId()],
            'copies' => 4,
            'totalCopies' => 5,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateBookSucceedsForLibrarian(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $author = $this->createAuthor('Eric Evans');
        $category = $this->createCategory('Design');

        $client = $this->createAuthenticatedClient($librarian);
        $this->jsonRequest($client, 'POST', '/api/books', [
            'title' => 'Domain-Driven Design',
            'authorId' => $author->getId(),
            'categoryIds' => [$category->getId()],
            'copies' => 4,
            'totalCopies' => 5,
        ]);

        $this->assertResponseStatusCodeSame(201);

        $data = $this->getJsonResponse($client);
        $this->assertSame('Domain-Driven Design', $data['title']);
        $this->assertSame('Eric Evans', $data['author']['name']);
        $this->assertSame(4, $data['copies']);
        $this->assertSame(5, $data['totalCopies']);
        $this->assertSame($category->getName(), $data['categories'][0]['name']);
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
        $originalAuthor = $this->createAuthor('Unknown Author');
        $initialCategory = $this->createCategory('Legacy');
        $book = $this->createBook('Legacy Title', $originalAuthor, 1, [$initialCategory], 2);
        $newAuthor = $this->createAuthor('Refactoring Guru');
        $newCategory = $this->createCategory('Refactoring');

        $client = $this->createAuthenticatedClient($librarian);
        $this->jsonRequest($client, 'PUT', '/api/books/' . $book->getId(), [
            'title' => 'Refactored Title',
            'authorId' => $newAuthor->getId(),
            'categoryIds' => [$newCategory->getId()],
        ]);

        $this->assertResponseStatusCodeSame(200);

        $updated = $this->entityManager->getRepository(Book::class)->find($book->getId());
        self::assertNotNull($updated);
        self::assertSame('Refactored Title', $updated->getTitle());
        self::assertSame(1, $updated->getCopies());
        self::assertSame(2, $updated->getTotalCopies());
        self::assertSame('Refactoring Guru', $updated->getAuthor()->getName());
        self::assertCount(1, $updated->getCategories());
    }

    public function testUpdateBookRejectsManualInventoryChanges(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $book = $this->createBook();

        $client = $this->createAuthenticatedClient($librarian);
        $this->jsonRequest($client, 'PUT', '/api/books/' . $book->getId(), [
            'copies' => 10,
            'totalCopies' => 12,
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testDeleteBook(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $author = $this->createAuthor('Disposable Author');
        $category = $this->createCategory('Archive');
        $book = $this->createBook('Disposable Book', $author, 1, [$category], 1);

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'DELETE', '/api/books/' . $book->getId());

        $this->assertResponseStatusCodeSame(204);

        $deleted = $this->entityManager->getRepository(Book::class)->find($book->getId());
        self::assertNull($deleted);
    }
}
