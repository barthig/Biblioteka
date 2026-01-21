<?php
namespace App\Tests\Functional;

class ExportControllerTest extends ApiTestCase
{
    public function testExportBooksReturnsCsv(): void
    {
        $this->createBook('Exported Book');

        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/books/export');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=utf-8');
    }
}
