<?php
namespace App\Controller;

use App\Application\Command\Review\CreateReviewCommand;
use App\Application\Command\Review\DeleteReviewCommand;
use App\Application\Query\Review\ListBookReviewsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Request\CreateReviewRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Review')]
class ReviewController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;

    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
        private SecurityService $security
    ) {
    }

    #[OA\Get(
        path: '/api/books/{id}/reviews',
        summary: 'List reviews for a book',
        tags: ['Reviews'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 404, description: 'Book not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(int $id, Request $request): JsonResponse
    {
        $query = new ListBookReviewsQuery(bookId: $id);

        try {
            $envelope = $this->queryBus->dispatch($query);
            $result = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($result, 200, [], ['groups' => ['review:read', 'book:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            if ($e->getMessage() === 'Book not found') {
                return $this->jsonError(ApiError::notFound('Book'));
            }
            return $this->jsonError(ApiError::internalError($e->getMessage()));
        }
    }

    #[OA\Post(
        path: '/api/books/{id}/reviews',
        summary: 'Create or update review',
        tags: ['Reviews'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['rating', 'comment'],
                properties: [
                    new OA\Property(property: 'rating', type: 'integer', minimum: 1, maximum: 5),
                    new OA\Property(property: 'comment', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function upsert(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonError(ApiError::unauthorized());
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new CreateReviewRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $command = new CreateReviewCommand(
            userId: (int) $payload['sub'],
            bookId: $id,
            rating: $dto->rating,
            comment: $dto->comment
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $review = $envelope->last(HandledStamp::class)?->getResult();
            
            // Check if it was a new review (would need additional info from handler)
            // For now, always return 200 as the handler does upsert
            return $this->json($review, 200, [], ['groups' => ['review:read', 'book:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            $statusCode = match ($e->getMessage()) {
                'User or book not found' => 404,
                default => 500
            };
            return $this->jsonErrorMessage($statusCode, $e->getMessage());
        }
    }

    #[OA\Delete(
        path: '/api/books/{id}/reviews',
        summary: 'Delete review',
        tags: ['Reviews'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Review not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function delete(int $id, Request $request): JsonResponse
    {
        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonError(ApiError::unauthorized());
        }

        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');

        $command = new DeleteReviewCommand(
            reviewId: $id,
            userId: (int) $payload['sub'],
            isLibrarian: $isLibrarian
        );

        try {
            $this->commandBus->dispatch($command);
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            $statusCode = match ($e->getMessage()) {
                'Review not found' => 404,
                default => str_contains($e->getMessage(), 'Forbidden') ? 403 : 500
            };
            return $this->jsonErrorMessage($statusCode, $e->getMessage());
        }
    }
}


