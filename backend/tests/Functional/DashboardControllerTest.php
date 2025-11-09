<?php
namespace App\Tests\Functional;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;

class DashboardControllerTest extends ApiTestCase
{
    public function testOverviewReturnsAggregatedCounts(): void
    {
        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');
        $book1 = $this->createBook('Book A', 'Author');
        $book2 = $this->createBook('Book B', 'Author');
    $loan1 = $this->createLoan($user1, $book1);
    $loan1->setDueAt(new \DateTimeImmutable('-2 days'));
    $loan2 = $this->createLoan($user2, $book2);
    $this->entityManager->flush();

        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/dashboard');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);

        $this->assertSame(2, $data['books']);
        $this->assertSame(2, $data['users']);
    $this->assertSame(2, $data['activeLoans']);
    $this->assertGreaterThanOrEqual(1, $data['overdueLoans']);
    }
}
