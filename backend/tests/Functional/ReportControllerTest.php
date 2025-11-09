<?php
namespace App\Tests\Functional;

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
}
