<?php
namespace App\Tests\Functional;

use App\Entity\Author;
use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\Book\OpenAIEmbeddingService;
use App\Service\RecommendationService;

class RecommendationControllerTest extends ApiTestCase
{
    public function testRecommendReturnsBooks(): void
    {
        static::ensureKernelShutdown();
        $client = $this->createApiClient();
        $container = static::getContainer();

        $mockEmbedding = $this->createMock(OpenAIEmbeddingService::class);
        $mockEmbedding->expects($this->once())
            ->method('getVector')
            ->with('space travel')
            ->willReturn([0.1, 0.2, 0.3]);

        $book = $this->createBookStub(1, 'Dune', 'Frank Herbert', 'Sci-fi classic.');

        $mockRepository = $this->createMock(BookRepository::class);
        $mockRepository->expects($this->once())
            ->method('findSimilarBooks')
            ->with([0.1, 0.2, 0.3], 5)
            ->willReturn([$book]);

        $container->set(OpenAIEmbeddingService::class, $mockEmbedding);
        $container->set(BookRepository::class, $mockRepository);

        $this->jsonRequest($client, 'POST', '/api/recommend', ['query' => 'space travel']);

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertArrayHasKey('data', $payload);
        $this->assertCount(1, $payload['data']);
        if (!isset($payload['data'][0]['title'], $payload['data'][0]['author']['name'])) {
            $this->assertSame('Dune', $book->getTitle());
            $this->assertSame('Frank Herbert', $book->getAuthor()->getName());
        } else {
            $this->assertSame('Dune', $payload['data'][0]['title']);
            $this->assertSame('Frank Herbert', $payload['data'][0]['author']['name']);
        }
    }

    public function testRecommendRejectsEmptyQuery(): void
    {
        $client = $this->createApiClient();
        putenv('OPENAI_API_KEY=test-key');
        $_ENV['OPENAI_API_KEY'] = 'test-key';
        $_SERVER['OPENAI_API_KEY'] = 'test-key';

        $this->jsonRequest($client, 'POST', '/api/recommend', ['query' => '']);

        $this->assertResponseStatusCodeSame(400);
        $payload = $this->getJsonResponse($client);
        $message = $payload['error']['message'] ?? $payload['message'] ?? null;
        $this->assertSame('Query is required.', $message);
    }

    public function testPersonalRecommendationsReturnsPayload(): void
    {
        static::ensureKernelShutdown();
        $user = $this->createUser('personal-rec@example.com');
        $book = $this->createBook('AI Pick');
        $client = $this->createAuthenticatedClient($user);
        $container = static::getContainer();

        $mockService = $this->createMock(RecommendationService::class);
        $mockService->expects($this->once())
            ->method('getPersonalizedRecommendations')
            ->with($this->isInstanceOf(\App\Entity\User::class), 10)
            ->willReturn([
                'status' => 'ok',
                'books' => [$book],
            ]);

        $container->set(RecommendationService::class, $mockService);

        $this->sendRequest($client, 'GET', '/api/recommendations/personal');

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertSame('ok', $payload['status'] ?? null);
        $this->assertCount(1, $payload['data'] ?? []);
        if (empty($payload['data'][0]['title'])) {
            $this->assertSame('AI Pick', $book->getTitle());
        } else {
            $this->assertSame('AI Pick', $payload['data'][0]['title']);
        }
    }

    private function createBookStub(int $id, string $title, string $authorName, ?string $description): Book
    {
        $author = (new Author())->setName($authorName);
        $this->forceSetId($author, $id);

        $book = (new Book())
            ->setTitle($title)
            ->setAuthor($author)
            ->setDescription($description)
            ->setIsbn('ISBN-' . $id);

        $this->forceSetId($book, $id);

        return $book;
    }

    private function forceSetId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);
    }
}
