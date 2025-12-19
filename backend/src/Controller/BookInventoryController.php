<?php
namespace App\Controller;

use App\Application\Command\BookInventory\CreateBookCopyCommand;
use App\Application\Command\BookInventory\DeleteBookCopyCommand;
use App\Application\Command\BookInventory\UpdateBookCopyCommand;
use App\Application\Query\BookInventory\ListBookCopiesQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
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

class BookInventoryController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;
    
    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
    ) {
    }
    
    public function list(int $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
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
            return $this->json(['message' => $e->getMessage()], 404);
        }
    }

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
                $statusCode = 409;
            }
            return $this->json(['message' => $e->getMessage()], $statusCode);
        }
    }

    public function update(
        int $id,
        int $copyId,
        Request $request,
        SecurityService $security,
        ValidatorInterface $validator
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
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
            return $this->json(['message' => $e->getMessage()], 404);
        }
    }

    public function delete(
        int $id,
        int $copyId,
        Request $request,
        SecurityService $security
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        try {
            $this->commandBus->dispatch(new DeleteBookCopyCommand($id, $copyId));
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    public function findByBarcode(
        string $barcode,
        Request $request,
        SecurityService $security,
        BookCopyRepository $copyRepo
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $copy = $copyRepo->findOneBy(['inventoryCode' => $barcode]);
        if (!$copy) {
            return $this->json(['message' => 'Nie znaleziono egzemplarza o tym kodzie kreskowym'], 404);
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
