<?php
namespace App\Controller;

use App\Application\Command\Acquisition\CancelOrderCommand;
use App\Application\Command\Acquisition\CreateOrderCommand;
use App\Application\Command\Acquisition\ReceiveOrderCommand;
use App\Application\Command\Acquisition\UpdateOrderStatusCommand;
use App\Application\Query\Acquisition\ListOrdersQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Request\CreateAcquisitionOrderRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AcquisitionOrderController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;
    
    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
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

    public function create(Request $request, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new CreateAcquisitionOrderRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $budgetId = null;
        if (!empty($data['budgetId'])) {
            if (!ctype_digit((string) $data['budgetId'])) {
                return $this->json(['error' => 'Invalid budgetId'], 400);
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
                $statusCode = 404;
            } elseif (str_contains($e->getMessage(), 'inactive') || str_contains($e->getMessage(), 'mismatch')) {
                $statusCode = 409;
            }
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function updateStatus(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid order id'], 400);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        if (empty($data['status'])) {
            return $this->json(['error' => 'Status is required'], 400);
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
            $statusCode = str_contains($e->getMessage(), 'not found') ? 404 : 409;
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function receive(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid order id'], 400);
        }

        $data = json_decode($request->getContent(), true) ?: [];

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
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    public function cancel(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid order id'], 400);
        }

        try {
            $this->commandBus->dispatch(new CancelOrderCommand((int) $id));
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            $statusCode = str_contains($e->getMessage(), 'not found') ? 404 : 409;
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }
}
