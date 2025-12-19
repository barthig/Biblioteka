<?php
namespace App\Controller;

use App\Repository\BookRepository;
use App\Service\OpenAIEmbeddingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RecommendationController extends AbstractController
{
    public function __construct(
        private readonly OpenAIEmbeddingService $embeddingService,
        private readonly BookRepository $bookRepository
    ) {}

    public function recommend(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $query = is_array($payload) ? trim((string) ($payload['query'] ?? '')) : '';

        if ($query === '') {
            return $this->json(['error' => 'Query is required.'], 400);
        }

        $vector = $this->embeddingService->getVector($query);
        $books = $this->bookRepository->findSimilarBooks($vector, 5);

        return $this->json(['data' => $books], 200, [], ['groups' => ['book:read']]);
    }
}
