<?php
namespace App\Controller;

use App\Application\Command\Recommendation\RemoveRecommendationFeedbackCommand;
use App\Application\Command\Recommendation\UpsertRecommendationFeedbackCommand;
use App\Entity\RecommendationFeedback;
use App\Repository\BookRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class RecommendationFeedbackController extends AbstractController
{
    public function __construct(
        private readonly SecurityService $security,
        private readonly BookRepository $bookRepo,
        private readonly MessageBusInterface $commandBus
    ) {}

    public function addFeedback(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $bookId = $data['bookId'] ?? null;
        $feedbackType = $data['feedbackType'] ?? null;

        if (!$bookId || !$feedbackType) {
            return $this->json(['error' => 'bookId and feedbackType are required'], 400);
        }

        if (!in_array($feedbackType, [RecommendationFeedback::TYPE_DISMISS, RecommendationFeedback::TYPE_INTERESTED])) {
            return $this->json(['error' => 'Invalid feedbackType'], 400);
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

    public function removeFeedback(int $bookId, Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $this->commandBus->dispatch(new RemoveRecommendationFeedbackCommand($userId, $bookId));

        return $this->json(['success' => true]);
    }
}
