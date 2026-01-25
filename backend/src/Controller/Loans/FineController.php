<?php
namespace App\Controller\Loans;

use App\Application\Command\Fine\CancelFineCommand;
use App\Application\Command\Fine\CreateFineCommand;
use App\Application\Command\Fine\PayFineCommand;
use App\Application\Query\Fine\ListFinesQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Request\CreateFineRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Fine')]
class FineController extends AbstractController
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
        path: '/api/fines',
        summary: 'List fines',
        tags: ['Fines'],
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
                            items: new OA\Items(ref: '#/components/schemas/Fine')
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

        $payload = $this->security->getJwtPayload($request);
        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        $userId = null;

        if (!$isLibrarian) {
            if (!$payload || !isset($payload['sub'])) {
                return $this->jsonError(ApiError::unauthorized());
            }
            $userId = (int) $payload['sub'];
        }

        $query = new ListFinesQuery(
            page: $page,
            limit: $limit,
            userId: $userId,
            isLibrarian: $isLibrarian
        );

        $envelope = $this->queryBus->dispatch($query);
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result, 200, [], [
            'groups' => ['fine:read', 'loan:read'],
            'json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION,
        ]);
    }

    #[OA\Post(
        path: '/api/fines',
        summary: 'Create fine',
        tags: ['Fines'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['loanId', 'amount', 'currency'],
                properties: [
                    new OA\Property(property: 'loanId', type: 'integer'),
                    new OA\Property(property: 'amount', type: 'string'),
                    new OA\Property(property: 'currency', type: 'string'),
                    new OA\Property(property: 'reason', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Fine')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Loan not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new CreateFineRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $command = new CreateFineCommand(
            loanId: $dto->loanId,
            amount: (string) $dto->amount,
            currency: $dto->currency,
            reason: $dto->reason
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $fine = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($fine, 201, [], [
                'groups' => ['fine:read', 'loan:read'],
                'json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION,
            ]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            if ($e->getMessage() === 'Loan not found') {
                return $this->jsonError(ApiError::notFound('Loan'));
            }
            return $this->jsonError(ApiError::internalError($e->getMessage()));
        }
    }

    #[OA\Post(
        path: '/api/fines/{id}/pay',
        summary: 'Pay fine',
        tags: ['Fines'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Fine')),
            new OA\Response(response: 400, description: 'Invalid id or already paid', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Fine not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function pay(string $id, Request $request): JsonResponse
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid fine id'));
        }

        $payload = $this->security->getJwtPayload($request);
        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        $userId = $payload && isset($payload['sub']) ? (int) $payload['sub'] : 0;

        $command = new PayFineCommand(
            fineId: (int) $id,
            userId: $userId,
            isLibrarian: $isLibrarian
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $fine = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($fine, 200, [], [
                'groups' => ['fine:read', 'loan:read'],
                'json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION,
            ]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            $errorMessage = $e->getMessage();
            $error = match ($errorMessage) {
                'Fine not found' => ApiError::notFound('Fine'),
                'Fine already paid' => ApiError::badRequest('Fine already paid'),
                'Forbidden' => ApiError::forbidden(),
                default => ApiError::internalError($errorMessage)
            };
            return $this->jsonError($error);
        }
    }

    #[OA\Delete(
        path: '/api/fines/{id}',
        summary: 'Cancel fine',
        tags: ['Fines'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Cancelled'),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Fine not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function cancel(string $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid fine id'));
        }

        $command = new CancelFineCommand(fineId: (int) $id);

        try {
            $this->commandBus->dispatch($command);
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            $errorMessage = $e->getMessage();
            $error = match ($errorMessage) {
                'Fine not found' => ApiError::notFound('Fine'),
                'Cannot cancel a paid fine' => ApiError::badRequest('Cannot cancel a paid fine'),
                default => ApiError::internalError($errorMessage)
            };
            return $this->jsonError($error);
        }
    }
}

