<?php
namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\BookRepository;
use App\Service\RecommendationService;
use App\Service\SecurityService;
use App\Service\OpenAIEmbeddingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RecommendationController extends AbstractController
{
    public function __construct(
        private readonly OpenAIEmbeddingService $embeddingService,
        private readonly BookRepository $bookRepository,
        private readonly RecommendationService $recommendationService,
        private readonly SecurityService $security,
        private readonly UserRepository $users
    ) {}

    public function recommend(Request $request): JsonResponse
    {
        $start = microtime(true);
        $payload = json_decode($request->getContent(), true);
        $query = is_array($payload) ? trim((string) ($payload['query'] ?? '')) : '';

        if ($query === '') {
            return $this->json(['message' => 'Query is required.'], 400);
        }

        $embeddingStart = microtime(true);
        $vector = $this->embeddingService->getVector($query);
        $embeddingMs = (int) round((microtime(true) - $embeddingStart) * 1000);
        error_log('RecommendationController::recommend - embedding in ' . $embeddingMs . 'ms');
        $queryStart = microtime(true);
        $books = $this->bookRepository->findSimilarBooks($vector, 5);
        $queryMs = (int) round((microtime(true) - $queryStart) * 1000);
        $totalMs = (int) round((microtime(true) - $start) * 1000);
        error_log('RecommendationController::recommend - query in ' . $queryMs . 'ms, total ' . $totalMs . 'ms');

        return $this->json(['data' => $books], 200, [], ['groups' => ['book:read']]);
    }

    public function personal(Request $request): JsonResponse
    {
        $start = microtime(true);
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $user = $this->users->find($userId);
        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $recoStart = microtime(true);
        $result = $this->recommendationService->getPersonalizedRecommendations($user, 10);
        $recoMs = (int) round((microtime(true) - $recoStart) * 1000);
        $totalMs = (int) round((microtime(true) - $start) * 1000);
        error_log('RecommendationController::personal - reco in ' . $recoMs . 'ms, total ' . $totalMs . 'ms');

        return $this->json(
            [
                'status' => $result['status'],
                'data' => $result['books'],
            ],
            200,
            [],
            ['groups' => ['book:read']]
        );
    }
}
