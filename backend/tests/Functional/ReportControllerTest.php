<?php
namespace App\Tests\Functional;

use App\Entity\BookCopy;
use App\Entity\User;

class ReportControllerTest extends ApiTestCase
{
    public function testUsageReportRequiresLibrarian(): void
    {
        $user = $this->createUser('user@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->sendRequest($client, 'GET', '/api/reports/usage');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUsageReportValidatesDateRange(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'GET', '/api/reports/usage?from=invalid');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUsageReportReturns204WhenNoLoans(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'GET', '/api/reports/usage');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testUsageReportReturnsAggregatedData(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $user = $this->createUser('user@example.com');
        $book = $this->createBook();
        $this->createLoan($user, $book);

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'GET', '/api/reports/usage?from=-1 week&to=now');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertSame(1, $data['totalLoans']);
        $this->assertSame(1, $data['activeLoans']);
    }

    public function testExportRequiresLibrarian(): void
    {
        $user = $this->createUser('user@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->sendRequest($client, 'GET', '/api/reports/export?format=json');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testExportRequiresFormatParameter(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'GET', '/api/reports/export');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testExportValidatesFormat(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'GET', '/api/reports/export?format=xml');

        $this->assertResponseStatusCodeSame(422);
    }

    public function testExportGeneratesContent(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'GET', '/api/reports/export?format=json');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertSame('json', $data['format']);
    }

    public function testExportSimulatedFailure(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'GET', '/api/reports/export?format=pdf&simulateFailure=1');

        $this->assertResponseStatusCodeSame(500);
    }

    public function testPopularTitlesRequiresLibrarian(): void
    {
        $user = $this->createUser('reader-popular@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->sendRequest($client, 'GET', '/api/reports/circulation/popular');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPopularTitlesReturnsLoanCounts(): void
    {
        $librarian = $this->createUser('popular-lib@example.com', ['ROLE_LIBRARIAN']);
        $borrower = $this->createUser('popular-borrower@example.com');
        $book = $this->createBook('Popular Title');
        $this->createLoan($borrower, $book);
        $this->createLoan($borrower, $book, null, true);

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'GET', '/api/reports/circulation/popular?limit=5&days=365');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertSame(5, $data['limit']);
        $this->assertSame(365, $data['periodDays']);
        $this->assertNotEmpty($data['items']);
        $this->assertSame($book->getId(), $data['items'][0]['bookId']);
        $this->assertSame(2, $data['items'][0]['loanCount']);
    }

    public function testPatronSegmentsAggregatesStats(): void
    {
        $librarian = $this->createUser('segments-lib@example.com', ['ROLE_LIBRARIAN']);
        $student = $this->createUser('segments-student@example.com');
        $student->setMembershipGroup(User::GROUP_STUDENT)->block('Overdue fines');
        $this->entityManager->persist($student);

        $researcher = $this->createUser('segments-researcher@example.com');
        $researcher->setMembershipGroup(User::GROUP_RESEARCHER)->setLoanLimit(12);
        $this->entityManager->persist($researcher);
        $this->entityManager->flush();

        $book = $this->createBook('Segment Book');
        $this->createLoan($researcher, $book);

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'GET', '/api/reports/patrons/segments');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertArrayHasKey('segments', $data);
        $segments = $data['segments'];

        $studentSegment = null;
        $researcherSegment = null;
        foreach ($segments as $segment) {
            if ($segment['membershipGroup'] === User::GROUP_STUDENT) {
                $studentSegment = $segment;
            }
            if ($segment['membershipGroup'] === User::GROUP_RESEARCHER) {
                $researcherSegment = $segment;
            }
        }

        $this->assertNotNull($studentSegment);
        $this->assertSame(1, $studentSegment['totalUsers']);
        $this->assertSame(1, $studentSegment['blockedUsers']);
        $this->assertSame(0, $studentSegment['activeLoans']);
        $this->assertSame(5.0, $studentSegment['avgLoanLimit']);

        $this->assertNotNull($researcherSegment);
        $this->assertSame(1, $researcherSegment['totalUsers']);
        $this->assertSame(0, $researcherSegment['blockedUsers']);
        $this->assertSame(1, $researcherSegment['activeLoans']);
        $this->assertSame(12.0, $researcherSegment['avgLoanLimit']);
    }

    public function testFinancialSummaryCombinesBudgetAndFines(): void
    {
        $librarian = $this->createUser('financial-lib@example.com', ['ROLE_LIBRARIAN']);
        $budget = $this->createBudget('Finance 2025', '2025', '2000.00');
        $budget->setSpentAmount('500.00');
        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        $user = $this->createUser('financial-reader@example.com');
        $book = $this->createBook('Finance Book');
        $loanOutstanding = $this->createLoan($user, $book);
        $this->createFineForLoan($loanOutstanding, '10.00', false, 'Damage');

        $loanPaid = $this->createLoan($user, $book, null, true);
        $this->createFineForLoan($loanPaid, '5.50', true, 'Late fee');

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'GET', '/api/reports/financial');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertSame(2000.0, $data['budgets']['allocated']);
        $this->assertSame(500.0, $data['budgets']['spent']);
        $this->assertSame(1500.0, $data['budgets']['remaining']);
        $this->assertSame(10.0, $data['fines']['outstanding']);
        $this->assertSame(5.5, $data['fines']['collected']);
    }

    public function testInventoryOverviewReturnsBreakdown(): void
    {
        $librarian = $this->createUser('inventory-lib@example.com', ['ROLE_LIBRARIAN']);
        $book = $this->createBook('Inventory Source', null, 2, null, 3);
        $borrower = $this->createUser('inventory-borrower@example.com');
        $this->createLoan($borrower, $book);

        foreach ($book->getInventory() as $inventoryCopy) {
            if ($inventoryCopy->getStatus() === BookCopy::STATUS_AVAILABLE) {
                $inventoryCopy->setStatus(BookCopy::STATUS_WITHDRAWN);
                $this->entityManager->persist($inventoryCopy);
                break;
            }
        }
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'GET', '/api/reports/inventory');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertSame(3, $data['totalCopies']);
        $this->assertSame(33.33, $data['borrowedPercentage']);

        $statusMap = [];
        foreach ($data['copies'] as $entry) {
            $statusMap[$entry['status']] = $entry['total'];
        }

        $this->assertArrayHasKey(BookCopy::STATUS_BORROWED, $statusMap);
        $this->assertSame(1, $statusMap[BookCopy::STATUS_BORROWED]);
        $this->assertArrayHasKey(BookCopy::STATUS_WITHDRAWN, $statusMap);
        $this->assertSame(1, $statusMap[BookCopy::STATUS_WITHDRAWN]);
    }
}
