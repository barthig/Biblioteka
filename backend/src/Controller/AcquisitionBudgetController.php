<?php
namespace App\Controller;

use App\Application\Command\Acquisition\AddBudgetExpenseCommand;
use App\Application\Command\Acquisition\CreateBudgetCommand;
use App\Application\Command\Acquisition\UpdateBudgetCommand;
use App\Application\Query\Acquisition\GetBudgetSummaryQuery;
use App\Application\Query\Acquisition\ListBudgetsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Request\CreateAcquisitionBudgetRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AcquisitionBudgetController extends AbstractController
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

        $year = $request->query->get('year');
        $envelope = $this->queryBus->dispatch(new ListBudgetsQuery($year));
        $budgets = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($budgets, 200, [], ['groups' => ['budget:read']]);
    }

    public function create(Request $request, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new CreateAcquisitionBudgetRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        try {
            $command = new CreateBudgetCommand(
                (string) $data['name'],
                (string) $data['fiscalYear'],
                (string) $data['allocatedAmount'],
                isset($data['currency']) ? (string) $data['currency'] : 'PLN',
                isset($data['spentAmount']) && is_numeric($data['spentAmount']) ? (string) $data['spentAmount'] : null
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $budget = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($budget, 201, [], ['groups' => ['budget:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function update(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid budget id'], 400);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        try {
            $command = new UpdateBudgetCommand(
                (int) $id,
                isset($data['name']) ? (string) $data['name'] : null,
                isset($data['fiscalYear']) ? (string) $data['fiscalYear'] : null,
                isset($data['allocatedAmount']) && is_numeric($data['allocatedAmount']) ? (string) $data['allocatedAmount'] : null,
                isset($data['currency']) ? (string) $data['currency'] : null
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $budget = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($budget, 200, [], ['groups' => ['budget:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    public function addExpense(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid budget id'], 400);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        if (!isset($data['amount']) || !is_numeric($data['amount'])) {
            return $this->json(['error' => 'Amount must be numeric'], 400);
        }
        if (empty($data['description'])) {
            return $this->json(['error' => 'Description is required'], 400);
        }

        try {
            $command = new AddBudgetExpenseCommand(
                (int) $id,
                (string) $data['amount'],
                (string) $data['description'],
                isset($data['type']) ? (string) $data['type'] : null,
                isset($data['postedAt']) ? (string) $data['postedAt'] : null
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $expense = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($expense, 201, [], ['groups' => ['budget:read', 'acquisition:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            $statusCode = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function summary(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid budget id'], 400);
        }

        try {
            $envelope = $this->queryBus->dispatch(new GetBudgetSummaryQuery((int) $id));
            $payload = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($payload, 200, [], ['json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }
}
