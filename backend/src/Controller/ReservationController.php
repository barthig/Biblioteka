<?php
namespace App\Controller;

use App\Application\Command\Reservation\CancelReservationCommand;
use App\Application\Command\Reservation\CreateReservationCommand;
use App\Application\Query\Reservation\ListReservationsQuery;
use App\Controller\Traits\ValidationTrait;
use App\Request\CreateReservationRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
            expiresInDays: $dto->days ?? 2
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $reservation = $envelope->last(HandledStamp::class)->getResult();

            return $this->json(['data' => $reservation], 201, [], ['groups' => ['reservation:read']]);
        } catch (\Throwable $e) {
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? $e;
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
            
            if ($e instanceof \RuntimeException) {
                $statusCode = match ($e->getMessage()) {
                    'Reservation not found' => 404,
                    'Reservation already fulfilled' => 400,
                    'Forbidden' => 403,
                    default => 500
                };
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

        // Fulfill reservation = create loan from reserved copy
        // This will be handled by a command that:
        // 1. Checks if reservation is ACTIVE with assigned copy
        // 2. Creates loan for user with that copy
        // 3. Marks reservation as FULFILLED
        // 4. Updates copy status to LOANED

        try {
            // For now, we'll use a simple approach - get reservation and create loan manually
            $reservation = $this->queryBus->dispatch(new \App\Application\Query\Reservation\GetReservationQuery((int)$id))
                ->last(HandledStamp::class)->getResult();

            if (!$reservation) {
                return $this->json(['error' => 'Reservation not found'], 404);
            }

            if ($reservation->getStatus() !== 'ACTIVE') {
                return $this->json(['error' => 'Reservation is not active'], 400);
            }

            if (!$reservation->getBookCopy()) {
                return $this->json(['error' => 'No book copy assigned to this reservation'], 400);
            }

            // Create loan - default 30 days
            $createLoanCommand = new \App\Application\Command\Loan\CreateLoanCommand(
                userId: $reservation->getUser()->getId(),
                copyId: $reservation->getBookCopy()->getId(),
                durationDays: 30
            );

            $this->commandBus->dispatch($createLoanCommand);

            // Cancel reservation (mark as fulfilled)
            $cancelCommand = new CancelReservationCommand(
                reservationId: (int)$id,
                userId: $reservation->getUser()->getId(),
                isLibrarian: true
            );
            $this->commandBus->dispatch($cancelCommand);

            return $this->json(['message' => 'Reservation fulfilled, loan created'], 200);
        } catch (\Throwable $e) {
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? $e;
            }
            
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
