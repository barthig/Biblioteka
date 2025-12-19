<?php
namespace App\Controller;

use App\Application\Command\Favorite\AddFavoriteCommand;
use App\Application\Command\Favorite\RemoveFavoriteCommand;
use App\Application\Query\Favorite\ListUserFavoritesQuery;
use App\Controller\Traits\ValidationTrait;
use App\Entity\Favorite;
use App\Request\AddFavoriteRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FavoriteController extends AbstractController
{
    use ValidationTrait;

    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
        private SecurityService $security
    ) {
    }

    public function list(Request $request): JsonResponse
    {
        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $query = new ListUserFavoritesQuery(userId: (int) $payload['sub']);
        $envelope = $this->queryBus->dispatch($query);
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result, 200, [], ['groups' => ['favorite:read', 'book:read']]);
    }

    public function add(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
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
                return $this->json(['error' => $e->getMessage()], $e->getStatusCode());
            }

            $statusCode = match ($e->getMessage()) {
                'User or book not found' => 404,
                'Ksi????ka znajduje si?? ju?? na Twojej p????ce' => 409,
                default => 500
            };
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function remove(string $bookId, Request $request): JsonResponse
    {
        if (!ctype_digit($bookId) || (int) $bookId <= 0) {
            return $this->json(['error' => 'Invalid book id'], 400);
        }

        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
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
            if ($fav instanceof Favorite && $fav->getBook()?->getId() === (int) $bookId) {
                $favorite = $fav;
                break;
            }
        }

        if (!$favorite) {
            return $this->json(['error' => 'Pozycja nie znajduje si?? na Twojej p????ce'], 404);
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
                return $this->json(['error' => $e->getMessage()], $e->getStatusCode());
            }

            $statusCode = match ($e->getMessage()) {
                'Favorite not found' => 404,
                'You can only remove your own favorites' => 403,
                default => 500
            };
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }
}






