<?php
namespace App\Controller\User;

use App\Application\Command\Favorite\AddFavoriteCommand;
use App\Application\Command\Favorite\RemoveFavoriteCommand;
use App\Application\Query\Favorite\ListUserFavoritesQuery;
use App\Controller\Traits\ValidationTrait;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Entity\Favorite;
use App\Request\AddFavoriteRequest;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Favorite')]
class FavoriteController extends AbstractController
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
        path: '/api/favorites',
        summary: 'List user favorites',
        tags: ['Favorites'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonError(ApiError::unauthorized());
        }

        $query = new ListUserFavoritesQuery(userId: (int) $payload['sub']);
        $envelope = $this->queryBus->dispatch($query);
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result, 200, [], ['groups' => ['favorite:read', 'book:read']]);
    }

    #[OA\Post(
        path: '/api/favorites',
        summary: 'Add favorite',
        tags: ['Favorites'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['bookId'],
                properties: [
                    new OA\Property(property: 'bookId', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Already exists', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function add(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonError(ApiError::unauthorized());
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new AddFavoriteRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $command = new AddFavoriteCommand(
            userId: (int) $payload['sub'],
            bookId: $dto->bookId
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $favorite = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json(['data' => $favorite], 201, [], ['groups' => ['favorite:read', 'book:read']]);
        } catch (\Throwable $e) {
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? $e;
            }
            if ($e instanceof HttpExceptionInterface) {
                return $this->jsonError(ApiError::fromException($e));
            }

            $statusCode = match ($e->getMessage()) {
                'User or book not found' => 404,
                'Ksi????ka znajduje si?? ju?? na Twojej p????ce' => 409,
                default => 500
            };
            $errorMessage = $e->getMessage();
            $error = match ($statusCode) {
                404 => ApiError::notFound('User or book'),
                409 => ApiError::conflict($errorMessage),
                default => ApiError::internalError($errorMessage)
            };
            return $this->jsonError($error);
        }
    }

    #[OA\Delete(
        path: '/api/favorites/{bookId}',
        summary: 'Remove favorite by book id',
        tags: ['Favorites'],
        parameters: [
            new OA\Parameter(name: 'bookId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Removed'),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function removeByBookId(string $bookId, Request $request): JsonResponse
    {
        if (!ctype_digit($bookId) || (int) $bookId <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid book id'));
        }

        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonError(ApiError::unauthorized());
        }

        // Note: RemoveFavoriteCommand uses favoriteId, but the route uses bookId
        // We need to find the favorite first - this is a limitation that could be improved
        // by creating a RemoveFavoriteByBookCommand
        $query = new ListUserFavoritesQuery(userId: (int) $payload['sub']);
        $envelope = $this->queryBus->dispatch($query);
        $result = $envelope->last(HandledStamp::class)?->getResult();
        $favorites = $result['data'] ?? [];

        $favorite = null;
        foreach ($favorites as $fav) {
            if ($fav instanceof Favorite && $fav->getBook()->getId() === (int) $bookId) {
                $favorite = $fav;
                break;
            }
        }

        if (!$favorite) {
            return $this->jsonError(ApiError::notFound('Favorite item'));
        }

        $command = new RemoveFavoriteCommand(
            favoriteId: $favorite->getId(),
            userId: (int) $payload['sub']
        );

        try {
            $this->commandBus->dispatch($command);
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? $e;
            }
            if ($e instanceof HttpExceptionInterface) {
                return $this->jsonError(ApiError::fromException($e));
            }

            $statusCode = match ($e->getMessage()) {
                'Favorite not found' => 404,
                'You can only remove your own favorites' => 403,
                default => 500
            };
            $errorMessage = $e->getMessage();
            $error = match ($statusCode) {
                404 => ApiError::notFound('Favorite'),
                403 => ApiError::forbidden(),
                default => ApiError::internalError($errorMessage)
            };
            return $this->jsonError($error);
        }
    }
}




