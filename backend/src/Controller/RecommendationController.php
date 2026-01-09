<?php
namespace App\Controller;

use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Repository\UserRepository;
use App\Repository\BookRepository;
use App\Service\RecommendationService;
use App\Service\SecurityService;
use App\Service\OpenAIEmbeddingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Recommendation')]
class RecommendationController extends AbstractController
{
    public function __construct(
        private readonly OpenAIEmbeddingService $embeddingService,
        private readonly BookRepository $bookRepository,
        private readonly RecommendationService $recommendationService,
        private readonly SecurityService $security,
        private readonly UserRepository $users
    ) {}

    #[OA\Post(
        path: '/api/recommendations',
        summary: 'Rekomendacje na podstawie zapytania',
        description: 'Zwraca rekomendacje książek na podstawie zapytania tekstowego (wyszukiwanie semantyczne)',
        tags: ['Recommendations'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['query'],
                properties: [
                    new OA\Property(property: 'query', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Lista rekomendowanych książek', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Brak zapytania', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
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

    #[OA\Get(
        path: '/api/recommendations/personal',
        summary: 'Spersonalizowane rekomendacje',
        description: 'Zwraca spersonalizowane rekomendacje dla zalogowanego użytkownika na podstawie historii wypożyczeń i preferencji',
        tags: ['Recommendations'],
        responses: [
            new OA\Response(response: 200, description: 'Spersonalizowane rekomendacje', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Nieautoryzowany', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Użytkownik nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
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
