<?php
namespace App\Controller;

use App\Application\Command\Acquisition\CreateSupplierCommand;
use App\Application\Command\Acquisition\DeactivateSupplierCommand;
use App\Application\Command\Acquisition\UpdateSupplierCommand;
use App\Application\Query\Acquisition\ListSuppliersQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Request\CreateSupplierRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AcquisitionSupplierController extends AbstractController
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
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $activeParam = $request->query->get('active');
        $active = null;
        if ($activeParam !== null) {
            $active = filter_var($activeParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $query = new ListSuppliersQuery($active);
        $envelope = $this->queryBus->dispatch($query);
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result['items'] ?? [], 200, [], ['groups' => ['supplier:read']]);
    }

    public function create(Request $request, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new CreateSupplierRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $activeFlag = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($activeFlag === null) {
            return $this->json(['message' => 'Invalid active flag'], 400);
        }

        $command = new CreateSupplierCommand(
            name: (string) $data['name'],
            active: $activeFlag,
            contactEmail: $data['contactEmail'] ?? null,
            contactPhone: $data['contactPhone'] ?? null,
            addressLine: $data['addressLine'] ?? null,
            city: $data['city'] ?? null,
            country: $data['country'] ?? null,
            taxIdentifier: $data['taxIdentifier'] ?? null,
            notes: $data['notes'] ?? null
        );
        
        $envelope = $this->commandBus->dispatch($command);
        $supplier = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($supplier, 201, [], ['groups' => ['supplier:read']]);
    }

    public function update(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['message' => 'Invalid supplier id'], 400);
        }

        $data = json_decode($request->getContent(), true) ?: [];

        $activeValue = null;
        if (isset($data['active'])) {
            $activeValue = filter_var($data['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($activeValue === null && $data['active'] !== null) {
                return $this->json(['message' => 'Invalid active flag'], 400);
            }
        }

        try {
            $command = new UpdateSupplierCommand(
                (int) $id,
                $data['name'] ?? null,
                $data['contactEmail'] ?? null,
                $data['contactPhone'] ?? null,
                $data['addressLine'] ?? null,
                $data['city'] ?? null,
                $data['country'] ?? null,
                $data['taxIdentifier'] ?? null,
                $data['notes'] ?? null,
                $activeValue
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $supplier = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json($supplier, 200, [], ['groups' => ['supplier:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->json(['message' => $e->getMessage()], 404);
        }
    }

    public function deactivate(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['message' => 'Invalid supplier id'], 400);
        }

        try {
            $command = new DeactivateSupplierCommand((int) $id);
            $this->commandBus->dispatch($command);

            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->json(['message' => $e->getMessage()], 404);
        }
    }
}
