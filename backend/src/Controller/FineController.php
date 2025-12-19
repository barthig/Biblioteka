<?php
namespace App\Controller;

use App\Application\Command\Fine\CancelFineCommand;
use App\Application\Command\Fine\CreateFineCommand;
use App\Application\Command\Fine\PayFineCommand;
use App\Application\Query\Fine\ListFinesQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Request\CreateFineRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(10, $request->query->getInt('limit', 20)));

        $payload = $this->security->getJwtPayload($request);
        $isLibrarian = $this->security->hasRole($request, 'ROLE_LIBRARIAN');
        $userId = null;

        if (!$isLibrarian) {
            if (!$payload || !isset($payload['sub'])) {
                return $this->json(['error' => 'Unauthorized'], 401);
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

    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
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
            $statusCode = match ($e->getMessage()) {
                'Loan not found' => 404,
                default => 500
            };
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function pay(string $id, Request $request): JsonResponse
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid fine id'], 400);
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
            $statusCode = match ($e->getMessage()) {
                'Fine not found' => 404,
                'Fine already paid' => 400,
                'Forbidden' => 403,
                default => 500
            };
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function cancel(string $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid fine id'], 400);
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
            $statusCode = match ($e->getMessage()) {
                'Fine not found' => 404,
                'Cannot cancel a paid fine' => 400,
                default => 500
            };
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }
}
