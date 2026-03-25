<?php

namespace App\Tests\Functional;

use App\Entity\BookDigitalAsset;

class BookPublicAssetControllerTest extends ApiTestCase
{
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aN3cAAAAASUVORK5CYII=';

    public function testCoverReturnsPlaceholderWhenBookIsMissing(): void
    {
        $client = $this->createClientWithoutSecret();

        $client->request('GET', '/api/books/999999/cover');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $this->assertStringContainsString('image/svg+xml', (string) $client->getResponse()->headers->get('content-type'));
        $this->assertStringContainsString('Book not found', (string) $client->getResponse()->headers->get('x-cover-placeholder'));
    }

    public function testCoverReturnsPlaceholderForBookWithoutAsset(): void
    {
        $book = $this->createBook('Solaris');
        $client = $this->createClientWithoutSecret();

        $client->request('GET', '/api/books/' . $book->getId() . '/cover');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $this->assertStringContainsString('>S<', $content);
        $this->assertStringContainsString('Cover not found', (string) $client->getResponse()->headers->get('x-cover-placeholder'));
    }

    public function testCoverReturnsStoredImageWhenAssetExists(): void
    {
        $book = $this->createBook('Cover Book');
        $storageName = 'test-cover-' . $book->getId() . '.png';
        $assetDir = static::getContainer()->get('kernel')->getProjectDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'digital-assets';
        if (!is_dir($assetDir)) {
            mkdir($assetDir, 0775, true);
        }
        file_put_contents($assetDir . DIRECTORY_SEPARATOR . $storageName, base64_decode(self::PNG_BASE64, true));

        $asset = (new BookDigitalAsset())
            ->setBook($book)
            ->setLabel('cover')
            ->setOriginalFilename('cover.png')
            ->setMimeType('image/png')
            ->setSize(68)
            ->setStorageName($storageName);

        $book->addDigitalAsset($asset);
        $this->entityManager->persist($asset);
        $this->entityManager->flush();

        $client = $this->createClientWithoutSecret();
        $client->request('GET', '/api/books/' . $book->getId() . '/cover');

        $this->assertResponseStatusCodeSame(200);
        $this->assertStringContainsString('image/png', (string) $client->getResponse()->headers->get('content-type'));
        $this->assertNull($client->getResponse()->headers->get('x-cover-placeholder'));
    }
}
