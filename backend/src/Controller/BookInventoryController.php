<?php
namespace App\Controller;

use App\Application\Command\BookInventory\CreateBookCopyCommand;
use App\Application\Command\BookInventory\DeleteBookCopyCommand;
use App\Application\Command\BookInventory\UpdateBookCopyCommand;
use App\Application\Query\BookInventory\ListBookCopiesQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Entity\BookCopy;
use App\Repository\BookCopyRepository;
use App\Request\CreateBookCopyRequest;
use App\Request\UpdateBookCopyRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'BookInventory')]
class BookInventoryController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;
    
    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
    ) {
    }
    
    #[OA\Get(
        path: '/api/admin/books/{id}/copies',
        summary: 'List book copies',
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Book not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(int $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        try {
            $envelope = $this->queryBus->dispatch(new ListBookCopiesQuery($id));
            $result = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($result);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonError(ApiError::notFound($e->getMessage()));
        }
    }

    #[OA\Post(
        path: '/api/admin/books/{id}/copies',
        summary: 'Create book copy',
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'inventoryCode', type: 'string'),
                    new OA\Property(property: 'status', type: 'string'),
                    new OA\Property(property: 'accessType', type: 'string'),
                    new OA\Property(property: 'location', type: 'string', nullable: true),
                    new OA\Property(property: 'condition', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Book not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Already exists', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(
        int $id,
        Request $request,
        SecurityService $security,
        ValidatorInterface $validator
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        
        $dto = $this->mapArrayToDto($data, new CreateBookCopyRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        try {
            $command = new CreateBookCopyCommand(
                $id,
                $data['inventoryCode'] ?? '',
                $data['status'] ?? BookCopy::STATUS_AVAILABLE,
                $data['accessType'] ?? BookCopy::ACCESS_STORAGE,
                $data['location'] ?? null,
                $data['condition'] ?? null
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $copy = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($this->serializeCopy($copy), 201);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            $statusCode = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            if (str_contains($e->getMessage(), 'already exists')) {
                return $this->jsonError(ApiError::conflict($e->getMessage()));
            } elseif ($statusCode === 404) {
                return $this->jsonError(ApiError::notFound($e->getMessage()));
            }
            return $this->jsonError(ApiError::badRequest($e->getMessage()));
        }
    }

    #[OA\Put(
        path: '/api/admin/books/{id}/copies/{copyId}',
        summary: 'Update book copy',
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'copyId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', nullable: true),
                    new OA\Property(property: 'accessType', type: 'string', nullable: true),
                    new OA\Property(property: 'location', type: 'string', nullable: true),
                    new OA\Property(property: 'condition', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(
        int $id,
        int $copyId,
        Request $request,
        SecurityService $security,
        ValidatorInterface $validator
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $data = json_decode($request->getContent(), true) ?? [];
        
        $dto = $this->mapArrayToDto($data, new UpdateBookCopyRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        try {
            $command = new UpdateBookCopyCommand(
                $id,
                $copyId,
                $data['status'] ?? null,
                $data['accessType'] ?? null,
                $data['location'] ?? null,
                $data['condition'] ?? null
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $copy = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($this->serializeCopy($copy));
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonError(ApiError::notFound($e->getMessage()));
        }
    }

    #[OA\Delete(
        path: '/api/admin/books/{id}/copies/{copyId}',
        summary: 'Delete book copy',
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'copyId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function delete(
        int $id,
        int $copyId,
        Request $request,
        SecurityService $security
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        try {
            $this->commandBus->dispatch(new DeleteBookCopyCommand($id, $copyId));
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonError(ApiError::badRequest($e->getMessage()));
        }
    }

    #[OA\Get(
        path: '/api/admin/copies/barcode/{barcode}',
        summary: 'Find copy by barcode',
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'barcode', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function findByBarcode(
        string $barcode,
        Request $request,
        SecurityService $security,
        BookCopyRepository $copyRepo
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $copy = $copyRepo->findOneBy(['inventoryCode' => $barcode]);
        if (!$copy) {
            return $this->jsonError(ApiError::notFound('Nie znaleziono egzemplarza o tym kodzie kreskowym'));
        }

        return $this->json([
            'id' => $copy->getId(),
            'inventoryCode' => $copy->getInventoryCode(),
            'status' => $copy->getStatus(),
            'accessType' => $copy->getAccessType(),
            'location' => $copy->getLocation(),
            'condition' => $copy->getConditionState(),
            'bookId' => $copy->getBook()->getId(),
            'book' => [
                'id' => $copy->getBook()->getId(),
                'title' => $copy->getBook()->getTitle(),
                'author' => $copy->getBook()->getAuthor()?->getName(),
            ],
        ]);
    }

    private function serializeCopy(BookCopy $copy): array
    {
        return [
            'id' => $copy->getId(),
            'inventoryCode' => $copy->getInventoryCode(),
            'status' => $copy->getStatus(),
            'accessType' => $copy->getAccessType(),
            'location' => $copy->getLocation(),
            'condition' => $copy->getConditionState(),
            'bookId' => $copy->getBook()->getId(),
        ];
    }
}
