<?php
declare(strict_types=1);
namespace App\Controller\Books;

use App\Application\Command\Recommendation\RemoveRecommendationFeedbackCommand;
use App\Application\Command\Recommendation\UpsertRecommendationFeedbackCommand;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Entity\RecommendationFeedback;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'RecommendationFeedback')]
class RecommendationFeedbackController extends AbstractController
{
    use ExceptionHandlingTrait;
    
    public function __construct(
        private readonly SecurityService $security,
        private readonly MessageBusInterface $commandBus
    ) {}

    #[OA\Post(
        path: '/api/recommendation-feedback',
        summary: 'Dodaj feedback do rekomendacji',
        description: 'Zapisuje feedback użytkownika do rekomendowanej książki (zainteresowany/odrzucona)',
        tags: ['RecommendationFeedback'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['bookId', 'feedbackType'],
                properties: [
                    new OA\Property(property: 'bookId', type: 'integer'),
                    new OA\Property(property: 'feedbackType', type: 'string', enum: ['dismiss', 'interested'])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Feedback zapisany', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Nieautoryzowany', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function addFeedback(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->jsonErrorMessage(401, 'Unauthorized');
        }

        $data = json_decode($request->getContent(), true);
        $bookId = $data['bookId'] ?? null;
        $feedbackType = $data['feedbackType'] ?? null;

        if (!$bookId || !$feedbackType) {
            return $this->jsonErrorMessage(400, 'bookId and feedbackType are required');
        }

        if (!in_array($feedbackType, [RecommendationFeedback::TYPE_DISMISS, RecommendationFeedback::TYPE_INTERESTED])) {
            return $this->jsonErrorMessage(400, 'Invalid feedbackType');
        }

        $this->commandBus->dispatch(new UpsertRecommendationFeedbackCommand(
            userId: $userId,
            bookId: $bookId,
            feedbackType: $feedbackType
        ));

        return $this->json([
            'success' => true,
            'message' => $feedbackType === RecommendationFeedback::TYPE_DISMISS 
                ? 'Book dismissed from recommendations' 
                : 'Interest in book saved'
        ]);
    }

    #[OA\Delete(
        path: '/api/recommendation-feedback/{bookId}',
        summary: 'Usuń feedback do rekomendacji',
        description: 'Usuwa zapisany feedback użytkownika dla książki',
        tags: ['RecommendationFeedback'],
        parameters: [
            new OA\Parameter(name: 'bookId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Feedback usunięty', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Nieautoryzowany', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function removeFeedback(int $bookId, Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->jsonErrorMessage(401, 'Unauthorized');
        }

        $this->commandBus->dispatch(new RemoveRecommendationFeedbackCommand($userId, $bookId));

        return $this->json(['success' => true]);
    }
}

