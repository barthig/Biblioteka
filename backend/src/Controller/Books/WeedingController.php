<?php
namespace App\Controller\Books;

use App\Application\Command\Weeding\CreateWeedingRecordCommand;
use App\Application\Query\Weeding\ListWeedingRecordsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Request\CreateWeedingRecordRequest;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Weeding')]
class WeedingController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;
    
    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
    ) {
    }
    
    #[OA\Get(
        path: '/api/weeding',
        summary: 'List weeding records',
        tags: ['Weeding'],
        parameters: [new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 200))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $limit = $request->query->getInt('limit', 200);
        $envelope = $this->queryBus->dispatch(new ListWeedingRecordsQuery($limit));
        $records = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($records, 200, [], ['groups' => ['weeding:read', 'book:read', 'inventory:read']]);
    }

    #[OA\Post(
        path: '/api/weeding',
        summary: 'Create weeding record',
        tags: ['Weeding'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['bookId', 'copyId', 'reason'],
                properties: [
                    new OA\Property(property: 'bookId', type: 'integer'),
                    new OA\Property(property: 'copyId', type: 'integer'),
                    new OA\Property(property: 'reason', type: 'string'),
                    new OA\Property(property: 'action', type: 'string', nullable: true),
                    new OA\Property(property: 'conditionState', type: 'string', nullable: true),
                    new OA\Property(property: 'notes', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Conflict', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(Request $request, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new CreateWeedingRecordRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $payload = $security->getJwtPayload($request);
        $userId = null;
        if ($payload && isset($payload['sub']) && ctype_digit((string) $payload['sub'])) {
            $userId = (int) $payload['sub'];
        }

        try {
            $command = new CreateWeedingRecordCommand(
                $dto->bookId,
                $dto->copyId,
                (string) $data['reason'],
                $data['action'] ?? null,
                $data['conditionState'] ?? null,
                $data['notes'] ?? null,
                $data['removedAt'] ?? null,
                $userId
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $record = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($record, 201, [], ['groups' => ['weeding:read', 'book:read', 'inventory:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            $statusCode = 400;
            if (str_contains($e->getMessage(), 'not found')) {
                $statusCode = 404;
            } elseif (str_contains($e->getMessage(), 'borrowed') || str_contains($e->getMessage(), 'reserved') || str_contains($e->getMessage(), 'withdrawn') || str_contains($e->getMessage(), 'active')) {
                $statusCode = 409;
            }
            return $this->jsonErrorMessage($statusCode, $e->getMessage());
        }
    }
}

