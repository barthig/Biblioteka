<?php
namespace App\Controller;

use App\Application\Command\Reservation\CancelReservationCommand;
use App\Application\Command\Reservation\CreateReservationCommand;
use App\Application\Command\Reservation\FulfillReservationWorkflowCommand;
use App\Application\Command\Reservation\PrepareReservationCommand;
use App\Application\Query\Reservation\ListReservationsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Request\CreateReservationRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Reservation')]
class ReservationController extends AbstractController
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
        path: '/api/reservations',
        summary: 'List reservations',
        tags: ['Reservations'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'userId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'history', in: 'query', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Reservation')
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(10, $request->query->getInt('limit', 20)));
        $status = $request->query->get('status');
        $filterUserId = $request->query->has('userId') && ctype_digit((string) $request->query->get('userId'))
            ? (int) $request->query->get('userId')
            : null;
        $includeHistory = $request->query->getBoolean('history', false);

        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        $userId = null;

        if (!$isLibrarian) {
            $payload = $this->security->getJwtPayload($request);
            if (!$payload || !isset($payload['sub'])) {
                return $this->jsonError(ApiError::unauthorized());
            }
            $userId = (int)$payload['sub'];
            
            // Issue #16: Non-librarians can only see active reservations
            $includeHistory = false;
            if (!$status) {
                $status = 'ACTIVE';  // Force active filter
            }
        }

        $query = new ListReservationsQuery(
            userId: $userId,
            isLibrarian: $isLibrarian,
            page: $page,
            limit: $limit,
            status: $status,
            filterUserId: $filterUserId,
            includeHistory: $includeHistory
        );

        $envelope = $this->queryBus->dispatch($query);
        $result = $envelope->last(HandledStamp::class)->getResult();

        return $this->json($result, 200, [], ['groups' => ['reservation:read']]);
    }

    #[OA\Post(
        path: '/api/reservations',
        summary: 'Create reservation',
        tags: ['Reservations'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['bookId'],
                properties: [
                    new OA\Property(property: 'bookId', type: 'integer'),
                    new OA\Property(property: 'days', type: 'integer', nullable: true),
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/Reservation'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Conflict', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new CreateReservationRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $command = new CreateReservationCommand(
            userId: (int)$payload['sub'],
            bookId: (int)$dto->bookId,
            expiresInDays: $dto->days ?? 3  // Default 3 days (unified)
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $reservation = $envelope->last(HandledStamp::class)->getResult();

            return $this->json(['data' => $reservation], 201, [], ['groups' => ['reservation:read']]);
        } catch (\Throwable $e) {
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? $e;
            }

            if ($e instanceof HttpExceptionInterface) {
                return $this->json(['message' => $e->getMessage()], $e->getStatusCode());
            }

            if ($e instanceof \InvalidArgumentException) {
                return $this->json(['message' => $e->getMessage()], 400);
            }
            
            if ($e instanceof \RuntimeException) {
                $statusCode = match ($e->getMessage()) {
                    'User or book not found' => 404,
                    'Book currently available, wypożycz zamiast rezerwować' => 400,
                    'Masz już aktywną rezerwację na tę książkę' => 409,
                    default => 500
                };
                return $this->json(['message' => $e->getMessage()], $statusCode);
            }
            
            return $this->json(['message' => 'Internal error: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: '/api/reservations/{id}',
        summary: 'Cancel reservation',
        tags: ['Reservations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Cancelled'),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Reservation not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function cancel(string $id, Request $request): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['message' => 'Invalid reservation id'], 400);
        }

        $payload = $this->security->getJwtPayload($request);
        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');

        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $userId = (int)$payload['sub'];

        $command = new CancelReservationCommand(
            reservationId: (int)$id,
            userId: $userId,
            isLibrarian: $isLibrarian
        );

        try {
            $this->commandBus->dispatch($command);
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? $e;
            }

            if ($e instanceof HttpExceptionInterface) {
                return $this->json(['message' => $e->getMessage()], $e->getStatusCode());
            }
            
            if ($e instanceof \RuntimeException) {
                $statusCode = match ($e->getMessage()) {
                    'Reservation not found' => 404,
                    'Reservation already fulfilled' => 400,
                    'Forbidden' => 403,
                    'Reservation already cancelled' => 400,
                    'Reservation already expired' => 400,
                    default => 500
                };
                if ($statusCode === 500 && str_contains($e->getMessage(), 'Cannot cancel reservation')) {
                    $statusCode = 400;
                }
                if ($statusCode === 500 && str_contains($e->getMessage(), 'Cannot release copy')) {
                    $statusCode = 400;
                }
                return $this->json(['message' => $e->getMessage()], $statusCode);
            }
            
            return $this->json(['message' => 'Internal error'], 500);
        }
    }

    #[OA\Post(
        path: '/api/reservations/{id}/fulfill',
        summary: 'Fulfill reservation and create loan',
        tags: ['Reservations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Reservation not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function fulfill(string $id, Request $request): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['message' => 'Invalid reservation id'], 400);
        }

        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        if (!$isLibrarian) {
            return $this->json(['message' => 'Only librarians can fulfill reservations'], 403);
        }

        try {
            $this->commandBus->dispatch(
                new FulfillReservationWorkflowCommand(
                    reservationId: (int) $id,
                    actingUserId: $this->security->getCurrentUserId($request) ?? 0
                )
            );

            return $this->json(['message' => 'Reservation fulfilled, loan created'], 200);
        } catch (\Throwable $e) {
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? $e;
            }

            $statusCode = ($e instanceof HttpExceptionInterface)
                ? $e->getStatusCode()
                : 500;

            return $this->json(['message' => $e->getMessage()], $statusCode);
        }
    }

    #[OA\Post(
        path: '/api/reservations/{id}/prepare',
        summary: 'Mark reservation as prepared',
        tags: ['Reservations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Reservation not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function prepare(string $id, Request $request): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['message' => 'Invalid reservation id'], 400);
        }

        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        if (!$isLibrarian) {
            return $this->json(['message' => 'Only librarians can prepare reservations'], 403);
        }

        try {
            $this->commandBus->dispatch(
                new PrepareReservationCommand(
                    reservationId: (int) $id,
                    actingUserId: $this->security->getCurrentUserId($request) ?? 0
                )
            );

            return $this->json(['message' => 'Reservation marked as prepared, notification sent'], 200);
        } catch (\Throwable $e) {
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? $e;
            }

            $statusCode = ($e instanceof HttpExceptionInterface)
                ? $e->getStatusCode()
                : 500;

            return $this->json(['message' => $e->getMessage()], $statusCode);
        }
    }
}
