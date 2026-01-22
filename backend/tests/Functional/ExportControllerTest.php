<?php
namespace App\Tests\Functional;

class ExportControllerTest extends ApiTestCase
{
    public function testExportBooksReturnsCsv(): void
    {
        $this->createBook('Exported Book');

        $client = $this->createApiClient();
        $bufferLevel = ob_get_level();
        ob_start();
        $this->sendRequest($client, 'GET', '/api/books/export');
        while (ob_get_level() > $bufferLevel) {
            ob_end_clean();
        }

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=utf-8');
    }
}
