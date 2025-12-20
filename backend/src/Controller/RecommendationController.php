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
        $payload = json_decode($request->getContent(), true);
        $query = is_array($payload) ? trim((string) ($payload['query'] ?? '')) : '';

        if ($query === '') {
            return $this->json(['message' => 'Query is required.'], 400);
        }

        $vector = $this->embeddingService->getVector($query);
        $books = $this->bookRepository->findSimilarBooks($vector, 5);

        return $this->json(['data' => $books], 200, [], ['groups' => ['book:read']]);
    }

    public function personal(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $user = $this->users->find($userId);
        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $result = $this->recommendationService->getPersonalizedRecommendations($user, 10);

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
