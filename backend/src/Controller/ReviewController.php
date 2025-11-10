<?php
namespace App\Controller;

use App\Entity\Book;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\ReviewRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ReviewController extends AbstractController
{
    public function list(int $id, Request $request, ManagerRegistry $doctrine, ReviewRepository $repo, SecurityService $security): JsonResponse
    {
        $book = $doctrine->getRepository(Book::class)->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $summary = $repo->getSummaryForBook($book);
        $reviews = $repo->findByBook($book);

        $userReview = null;
        $payload = $security->getJwtPayload($request);
        if ($payload && isset($payload['sub'])) {
            $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
            if ($user) {
                $userReview = $repo->findOneByUserAndBook($user, $book);
            }
        }

        return $this->json([
            'summary' => $summary,
            'reviews' => $reviews,
            'userReview' => $userReview,
        ], 200, [], ['groups' => ['review:read', 'book:read']]);
    }

    public function upsert(int $id, Request $request, ManagerRegistry $doctrine, ReviewRepository $repo, SecurityService $security): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $book = $doctrine->getRepository(Book::class)->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $rating = isset($data['rating']) ? (int) $data['rating'] : null;
        $comment = isset($data['comment']) ? trim((string) $data['comment']) : null;

        if ($rating === null || $rating < 1 || $rating > 5) {
            return $this->json(['error' => 'Ocena musi być liczbą z zakresu 1-5'], 400);
        }

        $review = $repo->findOneByUserAndBook($user, $book);
        $statusCode = 200;

        if (!$review) {
            $review = (new Review())
                ->setBook($book)
                ->setUser($user);
            $statusCode = 201;
        }

        $review->setRating($rating)->setComment($comment)->touch();

        $em = $doctrine->getManager();
        $em->persist($review);
        $em->flush();

        return $this->json($review, $statusCode, [], ['groups' => ['review:read', 'book:read']]);
    }

    public function delete(int $id, Request $request, ManagerRegistry $doctrine, ReviewRepository $repo, SecurityService $security): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $book = $doctrine->getRepository(Book::class)->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $review = $repo->findOneByUserAndBook($user, $book);
        if (!$review) {
            return $this->json(['error' => 'Nie dodano jeszcze opinii do tej książki'], 404);
        }

        $em = $doctrine->getManager();
        $em->remove($review);
        $em->flush();

        return new JsonResponse(null, 204);
    }
}
