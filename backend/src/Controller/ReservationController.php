<?php
namespace App\Controller;

use App\Application\Command\Reservation\CancelReservationCommand;
use App\Application\Command\Reservation\CreateReservationCommand;
use App\Application\Command\Reservation\FulfillReservationWorkflowCommand;
use App\Application\Query\Reservation\ListReservationsQuery;
use App\Controller\Traits\ValidationTrait;
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

class ReservationController extends AbstractController
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
                return $this->json(['error' => 'Unauthorized'], 401);
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

    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
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
                return $this->json(['error' => $e->getMessage()], $e->getStatusCode());
            }

            if ($e instanceof \InvalidArgumentException) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            
            // Log the full exception for debugging
            error_log('ReservationController exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            if ($e instanceof \RuntimeException) {
                $statusCode = match ($e->getMessage()) {
                    'User or book not found' => 404,
                    'Book currently available, wypożycz zamiast rezerwować' => 400,
                    'Masz już aktywną rezerwację na tę książkę' => 409,
                    default => 500
                };
                return $this->json(['error' => $e->getMessage()], $statusCode);
            }
            
            return $this->json(['error' => 'Internal error: ' . $e->getMessage()], 500);
        }
    }

    public function cancel(string $id, Request $request): JsonResponse
    {
        error_log('ReservationController::cancel - id: ' . $id);
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['error' => 'Invalid reservation id'], 400);
        }

        $payload = $this->security->getJwtPayload($request);
        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');

        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $userId = (int)$payload['sub'];
        error_log('ReservationController::cancel - userId: ' . $userId . ', isLibrarian: ' . ($isLibrarian ? 'yes' : 'no'));

        $command = new CancelReservationCommand(
            reservationId: (int)$id,
            userId: $userId,
            isLibrarian: $isLibrarian
        );

        try {
            $this->commandBus->dispatch($command);
            error_log('ReservationController::cancel - SUCCESS, returning 204');
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            error_log('ReservationController::cancel - EXCEPTION: ' . $e->getMessage());
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? $e;
            }

            if ($e instanceof HttpExceptionInterface) {
                return $this->json(['error' => $e->getMessage()], $e->getStatusCode());
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
                return $this->json(['error' => $e->getMessage()], $statusCode);
            }
            
            return $this->json(['error' => 'Internal error'], 500);
        }
    }

    public function fulfill(string $id, Request $request): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['error' => 'Invalid reservation id'], 400);
        }

        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        if (!$isLibrarian) {
            return $this->json(['error' => 'Only librarians can fulfill reservations'], 403);
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

            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }
}
