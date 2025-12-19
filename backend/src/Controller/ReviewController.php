<?php
namespace App\Controller;

use App\Application\Command\Review\CreateReviewCommand;
use App\Application\Command\Review\DeleteReviewCommand;
use App\Application\Query\Review\ListBookReviewsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Request\CreateReviewRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
            $statusCode = match ($e->getMessage()) {
                'Book not found' => 404,
                default => 500
            };
            return $this->json(['message' => $e->getMessage()], $statusCode);
        }
    }

    public function upsert(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['message' => 'Unauthorized'], 401);
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
            return $this->json(['message' => $e->getMessage()], $statusCode);
        }
    }

    public function delete(int $id, Request $request): JsonResponse
    {
        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['message' => 'Unauthorized'], 401);
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
            return $this->json(['message' => $e->getMessage()], $statusCode);
        }
    }
}
