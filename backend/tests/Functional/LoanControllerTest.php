<?php
namespace App\Tests\Functional;

use App\Entity\Book;
use App\Entity\Loan;

class LoanControllerTest extends ApiTestCase
{
    public function testCreateLoanRequiresAuthentication(): void
    {
        $book = $this->createBook();
        $user = $this->createUser('borrower@example.com');

        $client = $this->createClientWithoutSecret();
        $this->jsonRequest($client, 'POST', '/api/loans', [
            'userId' => $user->getId(),
            'bookId' => $book->getId(),
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUserCanCreateLoanForSelf(): void
    {
        $user = $this->createUser('borrower@example.com');
        $book = $this->createBook('Borrowable Book', 'Author', 2);

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/loans', [
            'userId' => $user->getId(),
            'bookId' => $book->getId(),
            'days' => 7,
        ]);

        $this->assertResponseStatusCodeSame(201);

        $loan = $this->entityManager->getRepository(Loan::class)->findOneBy([]);
        self::assertNotNull($loan);
        self::assertSame($user->getId(), $loan->getUser()->getId());
        self::assertSame($book->getId(), $loan->getBook()->getId());

        $updatedBook = $this->entityManager->getRepository(Book::class)->find($book->getId());
        self::assertSame(1, $updatedBook->getCopies());
    }

    public function testCannotCreateLoanForAnotherUserWithoutRole(): void
    {
        $borrower = $this->createUser('borrower@example.com');
        $other = $this->createUser('other@example.com');
        $book = $this->createBook();

        $client = $this->createAuthenticatedClient($other);
        $this->jsonRequest($client, 'POST', '/api/loans', [
            'userId' => $borrower->getId(),
            'bookId' => $book->getId(),
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateLoanFailsWhenAlreadyBorrowed(): void
    {
        $user = $this->createUser('borrower@example.com');
        $book = $this->createBook();
        $this->createLoan($user, $book);

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/loans', [
            'userId' => $user->getId(),
            'bookId' => $book->getId(),
        ]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testBorrowerCanReturnLoan(): void
    {
        $user = $this->createUser('borrower@example.com');
        $book = $this->createBook('Book', 'Author', 0);
        $loan = $this->createLoan($user, $book);

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'PUT', '/api/loans/' . $loan->getId() . '/return');

        $this->assertResponseStatusCodeSame(200);

        $updatedLoan = $this->entityManager->getRepository(Loan::class)->find($loan->getId());
        self::assertNotNull($updatedLoan->getReturnedAt());

        $updatedBook = $this->entityManager->getRepository(Book::class)->find($book->getId());
        self::assertSame(1, $updatedBook->getCopies());
    }

    public function testReturnLoanForbiddenForAnotherUser(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $book = $this->createBook();
        $loan = $this->createLoan($owner, $book);

        $client = $this->createAuthenticatedClient($other);
        $this->sendRequest($client, 'PUT', '/api/loans/' . $loan->getId() . '/return');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testLibrarianCanListAllLoans(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');
        $book1 = $this->createBook('Book1', 'Author1');
        $book2 = $this->createBook('Book2', 'Author2');
        $this->createLoan($user1, $book1);
        $this->createLoan($user2, $book2);

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'GET', '/api/loans');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertCount(2, $data);
    }

    public function testUserSeesOnlyOwnLoans(): void
    {
        $user = $this->createUser('user@example.com');
        $other = $this->createUser('other@example.com');
        $book1 = $this->createBook('Book1', 'Author1');
        $book2 = $this->createBook('Book2', 'Author2');
        $this->createLoan($user, $book1);
        $this->createLoan($other, $book2);

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'GET', '/api/loans');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertCount(1, $data);
        $this->assertSame($user->getId(), $data[0]['user']['id']);
    }

    public function testListByUserReturns204WhenNoLoans(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $user = $this->createUser('user@example.com');

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'GET', '/api/loans/user/' . $user->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    public function testListByUserForbiddenWithoutRoleOrOwnership(): void
    {
        $user = $this->createUser('user@example.com');
        $other = $this->createUser('other@example.com');

        $client = $this->createAuthenticatedClient($other);
        $this->sendRequest($client, 'GET', '/api/loans/user/' . $user->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteLoanRequiresLibrarian(): void
    {
        $user = $this->createUser('user@example.com');
        $book = $this->createBook();
        $loan = $this->createLoan($user, $book);

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'DELETE', '/api/loans/' . $loan->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteLoanByLibrarianRestoresCopies(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $user = $this->createUser('user@example.com');
        $book = $this->createBook('Rare Book', 'Author', 0);
        $loan = $this->createLoan($user, $book);

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'DELETE', '/api/loans/' . $loan->getId());

        $this->assertResponseStatusCodeSame(204);

        $updatedBook = $this->entityManager->getRepository(Book::class)->find($book->getId());
        self::assertSame(1, $updatedBook->getCopies());
        $deleted = $this->entityManager->getRepository(Loan::class)->find($loan->getId());
        self::assertNull($deleted);
    }
}
