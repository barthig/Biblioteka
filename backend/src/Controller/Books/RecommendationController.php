<?php
namespace App\Controller\Books;

use App\Application\Query\Recommendation\FindSimilarBooksQuery;
use App\Application\Query\User\GetUserByIdQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Service\Book\RecommendationService;
use App\Service\Auth\SecurityService;
use App\Service\Book\OpenAIEmbeddingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Recommendation')]
class RecommendationController extends AbstractController
{
    use ExceptionHandlingTrait;
    
    public function __construct(
        private readonly OpenAIEmbeddingService $embeddingService,
        private readonly MessageBusInterface $queryBus,
        private readonly RecommendationService $recommendationService,
        private readonly SecurityService $security
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
        $payload = json_decode($request->getContent(), true);
        $query = is_array($payload) ? trim((string) ($payload['query'] ?? '')) : '';

        if ($query === '') {
            return $this->jsonErrorMessage(400, 'Query is required.');
        }

        $vector = $this->embeddingService->getVector($query);
        
        $envelope = $this->queryBus->dispatch(new FindSimilarBooksQuery($vector, 5));
        $books = $envelope->last(HandledStamp::class)?->getResult();

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
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->jsonErrorMessage(401, 'Unauthorized');
        }

        $envelope = $this->queryBus->dispatch(new GetUserByIdQuery($userId));
        $user = $envelope->last(HandledStamp::class)?->getResult();
        if (!$user) {
            return $this->jsonErrorMessage(404, 'User not found');
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

