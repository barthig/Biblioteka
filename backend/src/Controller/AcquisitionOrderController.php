<?php
namespace App\Controller;

use App\Application\Command\Acquisition\CancelOrderCommand;
use App\Application\Command\Acquisition\CreateOrderCommand;
use App\Application\Command\Acquisition\ReceiveOrderCommand;
use App\Application\Command\Acquisition\UpdateOrderStatusCommand;
use App\Application\Query\Acquisition\ListOrdersQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Request\CreateAcquisitionOrderRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'AcquisitionOrder')]
class AcquisitionOrderController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;
    
    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    #[OA\Get(
        path: '/api/orders',
        summary: 'Lista zamówień akwizycyjnych',
        description: 'Zwraca listę zamówień z filtrowaniem i paginacją. Wymaga roli LIBRARIAN.',
        tags: ['AcquisitionOrder'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 10, maximum: 100)),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'supplierId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'budgetId', in: 'query', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista zamówień', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(10, $request->query->getInt('limit', 20)));
        
        $status = null;
        if ($request->query->has('status')) {
            $statusParam = (string) $request->query->get('status');
            if ($statusParam !== '') {
                $status = strtoupper($statusParam);
            }
        }

        $supplierId = null;
        if ($request->query->has('supplierId') && ctype_digit((string) $request->query->get('supplierId'))) {
            $supplierId = (int) $request->query->get('supplierId');
        }

        $budgetId = null;
        if ($request->query->has('budgetId') && ctype_digit((string) $request->query->get('budgetId'))) {
            $budgetId = (int) $request->query->get('budgetId');
        }

        $query = new ListOrdersQuery($page, $limit, $status, $supplierId, $budgetId);
        $envelope = $this->queryBus->dispatch($query);
        $result = $envelope->last(HandledStamp::class)?->getResult();
        
        return $this->json($result, 200, [], ['groups' => ['acquisition:read', 'supplier:read', 'budget:read']]);
    }

    #[OA\Post(
        path: '/api/orders',
        summary: 'Utwórz zamówienie',
        description: 'Tworzy nowe zamówienie akwizycyjne. Wymaga roli LIBRARIAN.',
        tags: ['AcquisitionOrder'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['supplierId', 'title', 'totalAmount'],
                properties: [
                    new OA\Property(property: 'supplierId', type: 'integer'),
                    new OA\Property(property: 'budgetId', type: 'integer'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'totalAmount', type: 'string'),
                    new OA\Property(property: 'currency', type: 'string', default: 'PLN'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'referenceNumber', type: 'string'),
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'expectedAt', type: 'string'),
                    new OA\Property(property: 'status', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Zamówienie utworzone', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Dostawca lub budżet nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function create(Request $request, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        try {
            $data = $request->toArray();
        } catch (\Throwable) {
            $data = json_decode($request->getContent(), true);
            if (!is_array($data)) {
                $data = [];
            }
        }
        
        $dto = $this->mapArrayToDto($data, new CreateAcquisitionOrderRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $budgetId = null;
        if (!empty($data['budgetId'])) {
            if (!ctype_digit((string) $data['budgetId'])) {
                return $this->jsonError(ApiError::badRequest('Invalid budgetId'));
            }
            $budgetId = (int) $data['budgetId'];
        }

        try {
            $command = new CreateOrderCommand(
                $dto->supplierId,
                $budgetId,
                (string) $data['title'],
                (string) $data['totalAmount'],
                $dto->currency,
                $data['description'] ?? null,
                $data['referenceNumber'] ?? null,
                isset($data['items']) && is_array($data['items']) ? $data['items'] : null,
                !empty($data['expectedAt']) ? (string) $data['expectedAt'] : null,
                !empty($data['status']) ? (string) $data['status'] : null
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $order = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($order, 201, [], ['groups' => ['acquisition:read', 'supplier:read', 'budget:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            $statusCode = 400;
            if (str_contains($e->getMessage(), 'not found')) {
                return $this->jsonError(ApiError::notFound($e->getMessage()));
            } elseif (str_contains($e->getMessage(), 'inactive') || str_contains($e->getMessage(), 'mismatch')) {
                $statusCode = 409;
            }
            return $this->jsonError(ApiError::badRequest($e->getMessage()));
        }
    }

    #[OA\Put(
        path: '/api/orders/{id}/status',
        summary: 'Aktualizuj status zamówienia',
        description: 'Aktualizuje status zamówienia. Wymaga roli LIBRARIAN.',
        tags: ['AcquisitionOrder'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string'),
                    new OA\Property(property: 'orderedAt', type: 'string'),
                    new OA\Property(property: 'receivedAt', type: 'string'),
                    new OA\Property(property: 'expectedAt', type: 'string'),
                    new OA\Property(property: 'totalAmount', type: 'string'),
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object'))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Status zaktualizowany', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Zamówienie nie znalezione', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function updateStatus(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid order id'));
        }

        try {
            $data = $request->toArray();
        } catch (\Throwable) {
            $data = json_decode($request->getContent(), true);
            if (!is_array($data)) {
                $data = [];
            }
        }
        if (empty($data['status'])) {
            return $this->jsonError(ApiError::badRequest('Status is required'));
        }

        try {
            $command = new UpdateOrderStatusCommand(
                (int) $id,
                (string) $data['status'],
                !empty($data['orderedAt']) ? (string) $data['orderedAt'] : null,
                !empty($data['receivedAt']) ? (string) $data['receivedAt'] : null,
                isset($data['expectedAt']) ? (string) $data['expectedAt'] : null,
                isset($data['totalAmount']) && is_numeric($data['totalAmount']) ? (string) $data['totalAmount'] : null,
                isset($data['items']) && is_array($data['items']) ? $data['items'] : null
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $order = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($order, 200, [], ['groups' => ['acquisition:read', 'supplier:read', 'budget:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            if (str_contains($e->getMessage(), 'not found')) {
                return $this->jsonError(ApiError::notFound($e->getMessage()));
            }
            return $this->jsonError(ApiError::conflict($e->getMessage()));
        }
    }

    #[OA\Post(
        path: '/api/orders/{id}/receive',
        summary: 'Odbierz zamówienie',
        description: 'Oznacza zamówienie jako odebrane. Wymaga roli LIBRARIAN.',
        tags: ['AcquisitionOrder'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'receivedAt', type: 'string'),
                    new OA\Property(property: 'totalAmount', type: 'string'),
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'expenseAmount', type: 'string'),
                    new OA\Property(property: 'expenseDescription', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Zamówienie odebrane', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Zamówienie nie znalezione', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function receive(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid order id'));
        }

        $data = $request->request->all();
        if ($data === []) {
            try {
                $data = $request->toArray();
            } catch (\Throwable) {
                $data = json_decode($request->getContent(), true);
                if (!is_array($data)) {
                    $data = [];
                }
            }
        }

        try {
            $command = new ReceiveOrderCommand(
                (int) $id,
                !empty($data['receivedAt']) ? (string) $data['receivedAt'] : null,
                isset($data['totalAmount']) && is_numeric($data['totalAmount']) ? (string) $data['totalAmount'] : null,
                isset($data['items']) && is_array($data['items']) ? $data['items'] : null,
                isset($data['expenseAmount']) && is_numeric($data['expenseAmount']) ? (string) $data['expenseAmount'] : null,
                $data['expenseDescription'] ?? null
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $order = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($order, 200, [], ['groups' => ['acquisition:read', 'supplier:read', 'budget:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonError(ApiError::notFound($e->getMessage()));
        }
    }

    #[OA\Post(
        path: '/api/orders/{id}/cancel',
        summary: 'Anuluj zamówienie',
        description: 'Anuluje zamówienie. Wymaga roli LIBRARIAN.',
        tags: ['AcquisitionOrder'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'reason', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Zamówienie anulowane', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Zamówienie nie znalezione', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    #[OA\Post(
        path: '/api/orders/{id}/cancel',
        summary: 'Anuluj zamówienie',
        description: 'Anuluje zamówienie. Wymaga roli LIBRARIAN.',
        tags: ['AcquisitionOrder'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'reason', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Zamówienie anulowane', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Zamówienie nie znalezione', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function cancel(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid order id'));
        }

        try {
            $this->commandBus->dispatch(new CancelOrderCommand((int) $id));
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            if (str_contains($e->getMessage(), 'not found')) {
                return $this->jsonError(ApiError::notFound($e->getMessage()));
            }
            return $this->jsonError(ApiError::conflict($e->getMessage()));
        }
    }
}
