<?php
namespace App\Controller;

use App\Entity\Book;
use App\Entity\Rating;
use App\Repository\BookRepository;
use App\Repository\RatingRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RatingController extends AbstractController
{
    public function __construct(
        private readonly SecurityService $security,
        private readonly EntityManagerInterface $em,
        private readonly RatingRepository $ratingRepo,
        private readonly BookRepository $bookRepo
    ) {}

    public function listRatings(int $id, Request $request): JsonResponse
    {
        $book = $this->bookRepo->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
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
            'count' => $ratingCount ?? 0,
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
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $book = $this->bookRepo->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $user = $this->em->getRepository(\App\Entity\User::class)->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $ratingValue = $data['rating'] ?? null;
        $review = $data['review'] ?? null;

        if (!is_int($ratingValue) || $ratingValue < 1 || $ratingValue > 5) {
            return $this->json(['error' => 'Rating must be between 1 and 5'], 400);
        }

        // Check if user already rated this book
        $existingRating = $this->ratingRepo->findOneBy(['user' => $userId, 'book' => $book->getId()]);

        if ($existingRating) {
            $existingRating->setRating($ratingValue);
            if ($review !== null) {
                $existingRating->setReview($review);
            }
        } else {
            $existingRating = new Rating();
            $existingRating->setUser($user)
                ->setBook($book)
                ->setRating($ratingValue);
            if ($review) {
                $existingRating->setReview($review);
            }
            $this->em->persist($existingRating);
        }

        $this->em->flush();

        return $this->json([
            'success' => true,
            'rating' => [
                'id' => $existingRating->getId(),
                'rating' => $existingRating->getRating(),
                'review' => $existingRating->getReview(),
            ],
            'averageRating' => $this->ratingRepo->getAverageRatingForBook($book->getId()),
            'ratingCount' => $this->ratingRepo->getRatingCountForBook($book->getId())
        ]);
    }

    public function deleteRating(int $bookId, int $ratingId, Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $rating = $this->ratingRepo->find($ratingId);
        if (!$rating) {
            return $this->json(['error' => 'Rating not found'], 404);
        }

        if ($rating->getUser()->getId() !== $userId && 
            !$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $book = $rating->getBook();
        $this->em->remove($rating);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'averageRating' => $this->ratingRepo->getAverageRatingForBook($book->getId()),
            'ratingCount' => $this->ratingRepo->getRatingCountForBook($book->getId())
        ]);
    }

    public function userRatings(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->em->getRepository(\App\Entity\User::class)->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
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
                    'author' => $r->getBook()->getAuthor()?->getName(),
                ],
                'createdAt' => $r->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $r->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ], $ratings)
        ]);
    }
}
