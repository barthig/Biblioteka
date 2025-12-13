<?php
namespace App\Controller;

use App\Entity\RecommendationFeedback;
use App\Repository\BookRepository;
use App\Repository\RecommendationFeedbackRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RecommendationFeedbackController extends AbstractController
{
    public function __construct(
        private readonly SecurityService $security,
        private readonly EntityManagerInterface $em,
        private readonly RecommendationFeedbackRepository $feedbackRepo,
        private readonly BookRepository $bookRepo
    ) {}

    public function addFeedback(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->em->getRepository(\App\Entity\User::class)->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
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

        $book = $this->bookRepo->find($bookId);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        // Check if feedback already exists
        $existing = $this->feedbackRepo->findOneBy(['user' => $user, 'book' => $book]);

        if ($existing) {
            // Update existing feedback
            $existing->setFeedbackType($feedbackType);
        } else {
            // Create new feedback
            $feedback = new RecommendationFeedback();
            $feedback->setUser($user)
                ->setBook($book)
                ->setFeedbackType($feedbackType);
            $this->em->persist($feedback);
        }

        $this->em->flush();

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

        $user = $this->em->getRepository(\App\Entity\User::class)->find($userId);
        $book = $this->bookRepo->find($bookId);

        if (!$user || !$book) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $feedback = $this->feedbackRepo->findOneBy(['user' => $user, 'book' => $book]);
        if ($feedback) {
            $this->em->remove($feedback);
            $this->em->flush();
        }

        return $this->json(['success' => true]);
    }
}
