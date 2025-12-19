<?php
namespace App\Controller;

use App\Application\Command\Rating\DeleteRatingCommand;
use App\Application\Command\Rating\RateBookCommand;
use App\Entity\Rating;
use App\Repository\BookRepository;
use App\Repository\RatingRepository;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class RatingController extends AbstractController
{
    public function __construct(
        private readonly SecurityService $security,
        private readonly RatingRepository $ratingRepo,
        private readonly BookRepository $bookRepo,
        private readonly UserRepository $userRepository,
        private readonly MessageBusInterface $commandBus
    ) {}

    public function listRatings(int $id, Request $request): JsonResponse
    {
        $book = $this->bookRepo->find($id);
        if (!$book) {
            return $this->json(['message' => 'Book not found'], 404);
        }

        $ratings = $this->ratingRepo->findByBook($book);
        $avgRating = $this->ratingRepo->getAverageRatingForBook($book->getId());
        $ratingCount = $this->ratingRepo->getRatingCountForBook($book->getId());

        // Check if current user has rated
        $userRating = null;
        $userId = $this->security->getCurrentUserId($request);
        if ($userId) {
            $userRatings = $this->ratingRepo->findBy(['user' => $userId, 'book' => $book->getId()]);
            if (!empty($userRatings)) {
                $userRating = [
                    'id' => $userRatings[0]->getId(),
                    'rating' => $userRatings[0]->getRating(),
                    'review' => $userRatings[0]->getReview()
                ];
            }
        }

        return $this->json([
            'average' => $avgRating ? round($avgRating, 2) : 0,
            'count' => $ratingCount,
            'userRating' => $userRating,
            'ratings' => array_map(fn(Rating $r) => [
                'id' => $r->getId(),
                'rating' => $r->getRating(),
                'review' => $r->getReview(),
                'userName' => $r->getUser()->getName(),
                'createdAt' => $r->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $r->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ], $ratings)
        ]);
    }

    public function rateBook(int $id, Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $ratingValue = $data['rating'] ?? null;
        $review = $data['review'] ?? null;
        if (!is_int($ratingValue)) {
            return $this->json(['message' => 'Rating must be between 1 and 5'], 400);
        }

        $envelope = $this->commandBus->dispatch(new RateBookCommand(
            userId: $userId,
            bookId: $id,
            rating: $ratingValue,
            review: $review
        ));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json([
            'success' => true,
            'rating' => $result['rating'],
            'averageRating' => $result['averageRating'],
            'ratingCount' => $result['ratingCount']
        ]);
    }

    public function deleteRating(int $bookId, int $ratingId, Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $isAdmin = $this->security->hasRole($request, 'ROLE_ADMIN');
        $envelope = $this->commandBus->dispatch(new DeleteRatingCommand(
            userId: $userId,
            ratingId: $ratingId,
            isAdmin: $isAdmin
        ));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json([
            'success' => true,
            'averageRating' => $result['averageRating'],
            'ratingCount' => $result['ratingCount']
        ]);
    }

    public function userRatings(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $ratings = $this->ratingRepo->findBy(['user' => $userId]);

        return $this->json([
            'ratings' => array_map(fn(Rating $r) => [
                'id' => $r->getId(),
                'rating' => $r->getRating(),
                'review' => $r->getReview(),
                'book' => [
                    'id' => $r->getBook()->getId(),
                    'title' => $r->getBook()->getTitle(),
                    'author' => $r->getBook()->getAuthor()->getName(),
                ],
                'createdAt' => $r->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $r->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ], $ratings)
        ]);
    }
}
