<?php

namespace App\Tests\Integration;

use App\Entity\Book;
use App\Entity\Loan;
use App\Tests\Functional\ApiTestCase;

class LoanIntegrationTest extends ApiTestCase
{
    public function testCompleteBookLoanFlow(): void
    {
        $user = $this->createUser('integration-user@example.com');
        $author = $this->createAuthor('Integration Author');
        $category = $this->createCategory('Integration Loans');
        $book = $this->createBook('Integration Book', $author, 1, [$category], 2);

        $client = $this->createAuthenticatedClient($user);

        $this->sendRequest($client, 'GET', '/api/books/' . $book->getId());
        $this->assertResponseIsSuccessful();
        $bookPayload = $this->getJsonResponse($client);
        $this->assertSame($book->getId(), $bookPayload['id']);

        $this->jsonRequest($client, 'POST', '/api/loans', [
            'userId' => $user->getId(),
            'bookId' => $book->getId(),
            'days' => 14,
        ]);
        $this->assertResponseStatusCodeSame(201);

        $loan = $this->entityManager->getRepository(Loan::class)->findOneBy([
            'user' => $user,
            'book' => $book,
        ]);
        $this->assertNotNull($loan);
        $loanId = $loan->getId();
        $this->sendRequest($client, 'GET', '/api/me/loans');
        $this->assertResponseStatusCodeSame(200);
        $loansPayload = $this->getJsonResponse($client);
        $this->assertArrayHasKey('data', $loansPayload);
        $this->assertCount(1, $loansPayload['data']);

        $this->sendRequest($client, 'PUT', '/api/loans/' . $loanId . '/extend');
        $this->assertResponseStatusCodeSame(200);
        $extendPayload = $this->getJsonResponse($client);
        $this->assertArrayHasKey('data', $extendPayload);

        $this->sendRequest($client, 'PUT', '/api/loans/' . $loanId . '/return');
        $this->assertResponseStatusCodeSame(200);

        $this->entityManager->clear();
        $reloadedBook = $this->entityManager->getRepository(Book::class)->find($book->getId());
        $this->assertNotNull($reloadedBook);
        $this->assertSame(1, $reloadedBook->getCopies());
    }

    public function testReservationToLoanFlow(): void
    {
        $user = $this->createUser('integration-reservation@example.com');
        $book = $this->createBook('Reservation Book', null, 1, null, 0);

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/reservations', [
            'bookId' => $book->getId(),
        ]);

        $this->assertResponseStatusCodeSame(201);
        $reservation = $this->getJsonResponse($client);
        $this->assertArrayHasKey('data', $reservation);
        $this->assertArrayHasKey('id', $reservation['data']);

        $this->sendRequest($client, 'GET', '/api/reservations');
        $this->assertResponseStatusCodeSame(200);
        $reservations = $this->getJsonResponse($client);
        $this->assertArrayHasKey('data', $reservations);
        $this->assertCount(1, $reservations['data']);
    }

    public function testOverdueLoanCreatesfine(): void
    {
        $this->markTestSkipped('Requires time manipulation or cron job simulation');
    }
}

