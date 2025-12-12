<?php
namespace App\Controller;

use App\Application\Command\Weeding\CreateWeedingRecordCommand;
use App\Application\Query\Weeding\ListWeedingRecordsQuery;
use App\Controller\Traits\ValidationTrait;
use App\Request\CreateWeedingRecordRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WeedingController extends AbstractController
{
    use ValidationTrait;
    
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

        $limit = $request->query->getInt('limit', 200);
        $envelope = $this->queryBus->dispatch(new ListWeedingRecordsQuery($limit));
        $records = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($records, 200, [], ['groups' => ['weeding:read', 'book:read', 'inventory:read']]);
    }

    public function create(Request $request, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
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
        } catch (\RuntimeException $e) {
            $statusCode = 400;
            if (str_contains($e->getMessage(), 'not found')) {
                $statusCode = 404;
            } elseif (str_contains($e->getMessage(), 'borrowed') || str_contains($e->getMessage(), 'reserved') || str_contains($e->getMessage(), 'withdrawn') || str_contains($e->getMessage(), 'active')) {
                $statusCode = 409;
            }
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }
}
