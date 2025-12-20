<?php
namespace App\Tests\Unit;

use App\Service\OpenAIEmbeddingService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class OpenAIEmbeddingServiceTest extends TestCase
{
    public function testGetVectorReturnsEmbedding(): void
    {
        $response = new MockResponse(json_encode([
            'data' => [
                ['embedding' => [0.1, '0.2', 0.3]],
            ],
        ]));
        $client = new MockHttpClient($response);

        $service = new OpenAIEmbeddingService($client, 'test-key');

        $vector = $service->getVector('hello world');

        $this->assertEquals([0.1, 0.2, 0.3], $vector);
    }

    public function testGetVectorThrowsWhenMissingEmbedding(): void
    {
        $response = new MockResponse(json_encode([
            'data' => [
                ['missing' => 'embedding'],
            ],
        ]));
        $client = new MockHttpClient($response);

        $service = new OpenAIEmbeddingService($client, 'test-key');

        $this->expectException(\RuntimeException::class);
        $service->getVector('hello world');
    }
}
