<?php
declare(strict_types=1);
namespace App\Controller\Books;

use App\Application\Command\Rating\DeleteRatingCommand;
use App\Application\Command\Rating\RateBookCommand;
use App\Application\Query\User\GetUserByIdQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Entity\Rating;
use App\Repository\BookRepository;
use App\Repository\RatingRepository;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Rating')]
class RatingController extends AbstractController
{
    use ExceptionHandlingTrait;
    public function __construct(
        private readonly SecurityService $security,
        private readonly RatingRepository $ratingRepo,
        private readonly BookRepository $bookRepo,
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus
    ) {}

    #[OA\Get(
        path: '/api/books/{id}/ratings',
        summary: 'List ratings for a book',
        tags: ['Ratings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 404, description: 'Book not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function listRatings(int $id, Request $request): JsonResponse
    {
        $book = $this->bookRepo->find($id);
        if (!$book) {
            return $this->jsonError(ApiError::notFound('Book'));
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
                'userName' => $r->getUser()->getName(),
                'createdAt' => $r->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $r->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ], $ratings)
        ]);
    }

    #[OA\Post(
        path: '/api/books/{id}/rate',
        summary: 'Rate a book',
        tags: ['Ratings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['rating'],
                properties: [
                    new OA\Property(property: 'rating', type: 'integer', minimum: 1, maximum: 5),
                    new OA\Property(property: 'review', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function rateBook(int $id, Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->jsonError(ApiError::unauthorized());
        }

        $data = json_decode($request->getContent(), true);
        $ratingValue = $data['rating'] ?? null;
        $review = $data['review'] ?? null;
        if (!is_int($ratingValue)) {
            return $this->jsonError(ApiError::badRequest('Rating must be between 1 and 5'));
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

    #[OA\Delete(
        path: '/api/books/{bookId}/ratings/{ratingId}',
        summary: 'Delete rating',
        tags: ['Ratings'],
        parameters: [
            new OA\Parameter(name: 'bookId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ratingId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function deleteRating(int $bookId, int $ratingId, Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->jsonError(ApiError::unauthorized());
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

    #[OA\Get(
        path: '/api/users/me/ratings',
        summary: 'List current user ratings',
        tags: ['Ratings'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function userRatings(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->jsonError(ApiError::unauthorized());
        }

        $envelope = $this->queryBus->dispatch(new GetUserByIdQuery($userId));
        $user = $envelope->last(HandledStamp::class)?->getResult();
        if (!$user) {
            return $this->jsonError(ApiError::notFound('User'));
        }

        $ratings = $this->ratingRepo->findBy(['user' => $userId]);

        return $this->json([
            'ratings' => array_map(fn(Rating $r) => [
                'id' => $r->getId(),
                'rating' => $r->getRating(),
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

