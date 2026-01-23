<?php
namespace App\Controller;

use App\Application\Command\Acquisition\AddBudgetExpenseCommand;
use App\Application\Command\Acquisition\CreateBudgetCommand;
use App\Application\Command\Acquisition\UpdateBudgetCommand;
use App\Application\Query\Acquisition\GetBudgetSummaryQuery;
use App\Application\Query\Acquisition\ListBudgetsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Request\CreateAcquisitionBudgetRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'AcquisitionBudget')]
class AcquisitionBudgetController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;
    
    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    #[OA\Get(
        path: '/api/budgets',
        summary: 'Lista budżetów',
        description: 'Zwraca listę budżetów akwizycyjnych z opcjonalnym filtrowaniem po roku. Wymaga roli LIBRARIAN.',
        tags: ['AcquisitionBudget'],
        parameters: [
            new OA\Parameter(name: 'year', in: 'query', schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista budżetów', content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $year = $request->query->get('year');
        $envelope = $this->queryBus->dispatch(new ListBudgetsQuery($year));
        $budgets = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($budgets, 200, [], ['groups' => ['budget:read']]);
    }

    #[OA\Post(
        path: '/api/budgets',
        summary: 'Utwórz budżet',
        description: 'Tworzy nowy budżet akwizycyjny. Wymaga roli LIBRARIAN.',
        tags: ['AcquisitionBudget'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'fiscalYear', 'allocatedAmount'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'fiscalYear', type: 'string'),
                    new OA\Property(property: 'allocatedAmount', type: 'string'),
                    new OA\Property(property: 'currency', type: 'string', default: 'PLN'),
                    new OA\Property(property: 'spentAmount', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Budżet utworzony', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function create(Request $request, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
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
            return $this->jsonErrorMessage(400, $e->getMessage());
        }
    }

    #[OA\Put(
        path: '/api/budgets/{id}',
        summary: 'Aktualizuj budżet',
        description: 'Aktualizuje dane budżetu. Wymaga roli LIBRARIAN.',
        tags: ['AcquisitionBudget'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'fiscalYear', type: 'string'),
                    new OA\Property(property: 'allocatedAmount', type: 'string'),
                    new OA\Property(property: 'currency', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Budżet zaktualizowany', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Budżet nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function update(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonErrorMessage(400, 'Invalid budget id');
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
            return $this->jsonErrorMessage(404, $e->getMessage());
        }
    }

    #[OA\Post(
        path: '/api/budgets/{id}/expense',
        summary: 'Dodaj wydatek do budżetu',
        description: 'Dodaje nowy wydatek do budżetu. Wymaga roli LIBRARIAN.',
        tags: ['AcquisitionBudget'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'description'],
                properties: [
                    new OA\Property(property: 'amount', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'type', type: 'string'),
                    new OA\Property(property: 'postedAt', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Wydatek dodany', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Budżet nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function addExpense(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonErrorMessage(400, 'Invalid budget id');
        }

        $data = json_decode($request->getContent(), true) ?: [];
        if (!isset($data['amount']) || !is_numeric($data['amount'])) {
            return $this->jsonErrorMessage(400, 'Amount must be numeric');
        }
        if (empty($data['description'])) {
            return $this->jsonErrorMessage(400, 'Description is required');
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
            return $this->jsonErrorMessage($statusCode, $e->getMessage());
        }
    }

    #[OA\Get(
        path: '/api/budgets/{id}/summary',
        summary: 'Podsumowanie budżetu',
        description: 'Zwraca szczegółowe podsumowanie budżetu wraz z wydatkami. Wymaga roli LIBRARIAN.',
        tags: ['AcquisitionBudget'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Podsumowanie budżetu', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Budżet nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function summary(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonErrorMessage(400, 'Invalid budget id');
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
            return $this->jsonErrorMessage(404, $e->getMessage());
        }
    }
}

