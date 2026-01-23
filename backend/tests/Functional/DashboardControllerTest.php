<?php
namespace App\Tests\Functional;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;

class DashboardControllerTest extends ApiTestCase
{
    public function testOverviewReturnsAggregatedCounts(): void
    {
        $user1 = $this->createUser('user1@example.com', ['ROLE_LIBRARIAN']);
        $user2 = $this->createUser('user2@example.com');
        $book1 = $this->createBook('Book A', $this->createAuthor('Author A'));
        $book2 = $this->createBook('Book B', $this->createAuthor('Author B'));

        $loan1 = $this->createLoan($user1, $book1);
        $loan1->setDueAt(new \DateTimeImmutable('-2 days'));
        $loan2 = $this->createLoan($user2, $book2);
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($user1);
        $this->sendRequest($client, 'GET', '/api/dashboard');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);

        $this->assertSame(2, $data['data']['booksCount']);
        $this->assertSame(2, $data['data']['usersCount']);
        $this->assertSame(2, $data['data']['loansCount']);
        $this->assertSame(0, $data['data']['reservationsQueue']);
    }
}
