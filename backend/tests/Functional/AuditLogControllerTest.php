<?php
namespace App\Tests\Functional;

class AuditLogControllerTest extends ApiTestCase
{
    public function testAuditLogsRequireLibrarian(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/audit-logs');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAuditLogsReturnDataForLibrarian(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'GET', '/api/audit-logs');

        $this->assertResponseStatusCodeSame(200);
    }
}
