<?php
namespace App\Tests\Functional;

use App\Entity\BookDigitalAsset;

class BookAssetControllerTest extends ApiTestCase
{
    public function testListAssetsRequiresLibrarian(): void
    {
        $book = $this->createBook('Asset Book');
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/admin/books/' . $book->getId() . '/assets');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUploadDownloadAndDeleteAsset(): void
    {
        $book = $this->createBook('Asset Book');
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $payload = [
            'label' => 'PDF',
            'filename' => 'sample.pdf',
            'mimeType' => 'application/pdf',
            'content' => base64_encode('test-content')
        ];

        $this->jsonRequest($client, 'POST', '/api/admin/books/' . $book->getId() . '/assets', $payload);
        $this->assertResponseStatusCodeSame(201);

        $asset = $this->entityManager->getRepository(BookDigitalAsset::class)->findOneBy([
            'book' => $book
        ]);
        $this->assertNotNull($asset);

        $this->sendRequest($client, 'GET', '/api/admin/books/' . $book->getId() . '/assets');
        $this->assertResponseStatusCodeSame(200);

        $this->sendRequest($client, 'GET', '/api/admin/books/' . $book->getId() . '/assets/' . $asset->getId());
        $this->assertResponseStatusCodeSame(200);

        $this->sendRequest($client, 'DELETE', '/api/admin/books/' . $book->getId() . '/assets/' . $asset->getId());
        $this->assertResponseStatusCodeSame(204);
    }
}
