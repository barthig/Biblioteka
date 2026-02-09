<?php
declare(strict_types=1);
namespace App\Controller\Loans;

use App\Application\Command\Loan\CreateLoanCommand;
use App\Application\Command\Loan\DeleteLoanCommand;
use App\Application\Command\Loan\ExtendLoanCommand;
use App\Application\Command\Loan\ReturnLoanCommand;
use App\Application\Command\Loan\UpdateLoanCommand;
use App\Application\Query\Loan\GetLoanQuery;
use App\Application\Query\Loan\ListLoansQuery;
use App\Application\Query\Loan\ListUserLoansQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Request\CreateLoanRequest;
use App\Request\UpdateLoanRequest;
use App\Service\Auth\SecurityService;
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
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
        private readonly SecurityService $security
    ) {
    }

    private function handleException(\Throwable $e): JsonResponse
    {
        $e = $this->unwrapThrowable($e);

        // AppException hierarchy carries its own status code and error code
        if ($e instanceof \App\Exception\AppException) {
            $error = new ApiError(
                code: $e->getErrorCode() ?? 'ERROR',
                message: $e->getMessage(),
                statusCode: $e->getStatusCode(),
                details: $e->getContext() ?: null,
            );
            return $this->jsonError($error);
        }

        // Symfony HttpExceptions (NotFoundHttpException, BadRequestHttpException, etc.)
        $response = $this->jsonFromHttpException($e);
        if ($response) {
            return $response;
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
            new OA\Parameter(name: 'user', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'book', in: 'query', schema: new OA\Schema(type: 'string')),
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
        $userQuery = $request->query->get('user');
        $bookQuery = $request->query->get('book');

        if ($status === 'overdue') {
            $overdue = true;
            $status = null;
        }

        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        $userId = null;

        if (!$isLibrarian) {
            $payload = $this->security->getJwtPayload($request);
            if (!$payload || !isset($payload['sub'])) {
                return $this->jsonError(ApiError::unauthorized());
            }
            $userId = (int)$payload['sub'];
        }

        $query = new ListLoansQuery(
            userId: $userId,
            isLibrarian: $isLibrarian,
            page: $page,
            limit: $limit,
            status: $status,
            overdue: $overdue !== null ? filter_var($overdue, FILTER_VALIDATE_BOOLEAN) : null,
            userQuery: $userQuery,
            bookQuery: $bookQuery
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
            return $this->jsonError(ApiError::badRequest('Invalid id parameter'));
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
                return $this->jsonError(ApiError::notFound('Loan'));
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
            return $this->jsonError(ApiError::unauthorized());
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
                return $this->jsonError(ApiError::forbidden());
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
        path: '/api/me/loans',
        summary: 'List loans for current user',
        tags: ['Loans'],
        parameters: [
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
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function listMe(Request $request): JsonResponse
    {
        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonError(ApiError::unauthorized());
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(10, $request->query->getInt('limit', 20)));

        $query = new ListUserLoansQuery(
            userId: (int)$payload['sub'],
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
            return $this->jsonError(ApiError::badRequest('Invalid id parameter'));
        }

        $userId = (int)$id;
        $payload = $this->security->getJwtPayload($request);
        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        $isOwner = $payload && isset($payload['sub']) && (int)$payload['sub'] === $userId;

        if (!($isLibrarian || $isOwner)) {
            return $this->jsonError(ApiError::forbidden());
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
            return $this->jsonError(ApiError::badRequest('Invalid id parameter'));
        }

        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonError(ApiError::unauthorized());
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
                return $this->jsonError(ApiError::notFound('Loan'));
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
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid id parameter'));
        }

        $payload = $this->security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonError(ApiError::unauthorized());
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
                return $this->jsonError(ApiError::notFound('Loan'));
            }
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }

        $command = new ExtendLoanCommand(
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
            return $this->jsonError(ApiError::forbidden());
        }

        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid loan id'));
        }

        $command = new DeleteLoanCommand(loanId: (int)$id);

        try {
            $this->commandBus->dispatch($command);
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    #[OA\Put(
        path: '/api/loans/{id}',
        summary: 'Update loan (admin)',
        tags: ['Loans'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'dueAt', type: 'string'),
                    new OA\Property(property: 'status', type: 'string'),
                    new OA\Property(property: 'bookId', type: 'integer'),
                    new OA\Property(property: 'bookCopyId', type: 'integer'),
                ]
            )
        ),
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
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Loan not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(string $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid id parameter'));
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $dto = $this->mapArrayToDto($data, new UpdateLoanRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $command = new UpdateLoanCommand(
            loanId: (int)$id,
            dueAt: $dto->dueAt,
            status: $dto->status,
            bookId: $dto->bookId,
            bookCopyId: $dto->bookCopyId
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $loan = $envelope->last(HandledStamp::class)->getResult();

            if ($dto->status === 'returned' && $loan && $loan->getReturnedAt() === null) {
                $payload = $this->security->getJwtPayload($request);
                $userId = $payload && isset($payload['sub']) ? (int)$payload['sub'] : 0;
                $returnCommand = new ReturnLoanCommand(
                    loanId: (int)$id,
                    userId: $userId
                );
                $returnEnvelope = $this->commandBus->dispatch($returnCommand);
                $loan = $returnEnvelope->last(HandledStamp::class)->getResult();
            }

            return $this->json(['data' => $loan], 200, [], ['groups' => ['loan:read']]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}




