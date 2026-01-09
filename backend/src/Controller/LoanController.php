<?php
namespace App\Controller;

use App\Application\Command\Loan\CreateLoanCommand;
use App\Application\Command\Loan\DeleteLoanCommand;
use App\Application\Command\Loan\ExtendLoanCommand;
use App\Application\Command\Loan\ReturnLoanCommand;
use App\Application\Query\Loan\GetLoanQuery;
use App\Application\Query\Loan\ListLoansQuery;
use App\Application\Query\Loan\ListUserLoansQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Request\CreateLoanRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Loan')]
class LoanController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;

    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
        private SecurityService $security
    ) {
    }

    private function handleException(\Throwable $e): JsonResponse
    {
        $e = $this->unwrapThrowable($e);
        
        // Log the full exception for debugging
        error_log('LoanController exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        error_log('Stack trace: ' . $e->getTraceAsString());
        
        if ($e instanceof \RuntimeException) {
            $statusCode = match ($e->getMessage()) {
                'User not found', 'Book not found', 'Loan not found', 'Egzemplarz nie znaleziony' => 404,
                'Konto czytelnika jest zablokowane' => 423,
                'Forbidden' => 403,
                'Limit wypożyczeń został osiągnięty', 
                'Egzemplarz jest już wypożyczony',
                'Book reserved by another reader',
                'No copies available' => 409,
                default => 500
            };

            $error = match ($statusCode) {
                404 => ApiError::notFound('Resource'),
                423 => ApiError::locked($e->getMessage()),
                403 => ApiError::forbidden(),
                409 => ApiError::conflict($e->getMessage()),
                default => ApiError::internalError($e->getMessage())
            };

            return $this->jsonError($error);
        }
        
        return $this->jsonError(ApiError::internalError('Internal error'));
    }

    #[OA\Get(
        path: '/api/loans',
        summary: 'List loans',
        tags: ['Loans'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'overdue', in: 'query', schema: new OA\Schema(type: 'boolean')),
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
                            items: new OA\Items(ref: '#/components/schemas/Loan')
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
        $overdue = $request->query->get('overdue');

        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        $userId = null;

        if (!$isLibrarian) {
            $payload = $this->security->getJwtPayload($request);
            if (!$payload || !isset($payload['sub'])) {
                return $this->json(['message' => 'Unauthorized'], 401);
            }
            $userId = (int)$payload['sub'];
        }

        $query = new ListLoansQuery(
            userId: $userId,
            isLibrarian: $isLibrarian,
            page: $page,
            limit: $limit,
            status: $status,
            overdue: $overdue !== null ? filter_var($overdue, FILTER_VALIDATE_BOOLEAN) : null
        );

        $envelope = $this->queryBus->dispatch($query);
        $result = $envelope->last(HandledStamp::class)->getResult();

        return $this->json($result, 200, [], ['groups' => ['loan:read']]);
    }

    #[OA\Get(
        path: '/api/loans/{id}',
        summary: 'Get loan by id',
        tags: ['Loans'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Loan'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Loan not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function getLoan(string $id, Request $request): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['message' => 'Invalid id parameter'], 400);
        }

        $payload = $this->security->getJwtPayload($request);
        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        $userId = $payload['sub'] ?? 0;

        $query = new GetLoanQuery(
            loanId: (int)$id,
            userId: (int)$userId,
            isLibrarian: $isLibrarian
        );

        try {
            $envelope = $this->queryBus->dispatch($query);
            $loan = $envelope->last(HandledStamp::class)->getResult();

            if (!$loan) {
                return $this->json(['message' => 'Loan not found'], 404);
            }

            return $this->json(['data' => $loan], 200, [], ['groups' => ['loan:read']]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    #[OA\Post(
        path: '/api/loans',
        summary: 'Create loan',
        tags: ['Loans'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['userId', 'bookId'],
                properties: [
                    new OA\Property(property: 'userId', type: 'integer'),
                    new OA\Property(property: 'bookId', type: 'integer'),
                    new OA\Property(property: 'reservationId', type: 'integer', nullable: true),
                    new OA\Property(property: 'bookCopyId', type: 'integer', nullable: true),
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/Loan'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Conflict', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $payload = $this->security->getJwtPayload($request);
        if ($payload === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new CreateLoanRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        if (!$isLibrarian) {
            if (!isset($payload['sub']) || (int)$payload['sub'] !== (int)$dto->userId) {
                return $this->json(['message' => 'Forbidden'], 403);
            }
        }

        $command = new CreateLoanCommand(
            userId: (int)$dto->userId,
            bookId: (int)$dto->bookId,
            reservationId: $dto->reservationId,
            bookCopyId: $dto->bookCopyId
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $loan = $envelope->last(HandledStamp::class)->getResult();

            return $this->json(['data' => $loan], 201, [], ['groups' => ['loan:read']]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    #[OA\Get(
        path: '/api/loans/user/{id}',
        summary: 'List loans for user',
        tags: ['Loans'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
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
                            items: new OA\Items(ref: '#/components/schemas/Loan')
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 204, description: 'No content'),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function listByUser(string $id, Request $request): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['message' => 'Invalid id parameter'], 400);
        }

        $userId = (int)$id;
        $payload = $this->security->getJwtPayload($request);
        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        $isOwner = $payload && isset($payload['sub']) && (int)$payload['sub'] === $userId;

        if (!($isLibrarian || $isOwner)) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(10, $request->query->getInt('limit', 20)));

        $query = new ListUserLoansQuery(
            userId: $userId,
            page: $page,
            limit: $limit
        );

        $envelope = $this->queryBus->dispatch($query);
        $result = $envelope->last(HandledStamp::class)->getResult();

        if (empty($result['data'])) {
            return new JsonResponse(null, 204);
        }

        return $this->json($result, 200, [], ['groups' => ['loan:read']]);
    }

    #[OA\Put(
        path: '/api/loans/{id}/return',
        summary: 'Return loan',
        tags: ['Loans'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Loan'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Loan not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function returnLoan(string $id, Request $request): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['message' => 'Invalid id parameter'], 400);
        }

        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $userId = (int)$payload['sub'];
        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');

        // First check if user has access to this loan
        $getLoanQuery = new GetLoanQuery(
            loanId: (int)$id,
            userId: $userId,
            isLibrarian: $isLibrarian
        );

        try {
            $loanEnvelope = $this->queryBus->dispatch($getLoanQuery);
            $loan = $loanEnvelope->last(HandledStamp::class)->getResult();
            
            if (!$loan) {
                return $this->json(['message' => 'Loan not found'], 404);
            }
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }

        $command = new ReturnLoanCommand(
            loanId: (int)$id,
            userId: $userId
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $loan = $envelope->last(HandledStamp::class)->getResult();

            return $this->json(['data' => $loan], 200, [], ['groups' => ['loan:read']]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    #[OA\Put(
        path: '/api/loans/{id}/extend',
        summary: 'Extend loan',
        tags: ['Loans'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Loan'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Loan not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function extend(string $id, Request $request): JsonResponse
    {
        error_log('LoanController::extend - id: ' . $id);
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['message' => 'Invalid id parameter'], 400);
        }

        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $userId = (int)$payload['sub'];
        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        error_log('LoanController::extend - userId: ' . $userId . ', isLibrarian: ' . ($isLibrarian ? 'yes' : 'no'));

        // First check if user has access to this loan
        $getLoanQuery = new GetLoanQuery(
            loanId: (int)$id,
            userId: $userId,
            isLibrarian: $isLibrarian
        );

        try {
            $loanEnvelope = $this->queryBus->dispatch($getLoanQuery);
            $loan = $loanEnvelope->last(HandledStamp::class)->getResult();
            
            if (!$loan) {
                error_log('LoanController::extend - loan not found');
                return $this->json(['message' => 'Loan not found'], 404);
            }
            error_log('LoanController::extend - loan found, dispatching ExtendLoanCommand');
        } catch (\Throwable $e) {
            error_log('LoanController::extend - error getting loan: ' . $e->getMessage());
            return $this->handleException($e);
        }

        $command = new ExtendLoanCommand(
            loanId: (int)$id,
            userId: $userId
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $loan = $envelope->last(HandledStamp::class)->getResult();
            error_log('LoanController::extend - SUCCESS');

            return $this->json(['data' => $loan], 200, [], ['groups' => ['loan:read']]);
        } catch (\Throwable $e) {
            error_log('LoanController::extend - error extending loan: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    #[OA\Delete(
        path: '/api/loans/{id}',
        summary: 'Delete loan',
        tags: ['Loans'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Loan not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function delete(string $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['message' => 'Invalid loan id'], 400);
        }

        $command = new DeleteLoanCommand(loanId: (int)$id);

        try {
            $this->commandBus->dispatch($command);
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
