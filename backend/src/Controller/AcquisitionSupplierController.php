<?php
namespace App\Controller;

use App\Application\Command\Acquisition\CreateSupplierCommand;
use App\Application\Command\Acquisition\DeactivateSupplierCommand;
use App\Application\Command\Acquisition\UpdateSupplierCommand;
use App\Application\Query\Acquisition\ListSuppliersQuery;
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

        $active = $request->query->get('active');
        $includeInactive = true;
        if ($active !== null) {
            $includeInactive = !filter_var($active, FILTER_VALIDATE_BOOLEAN);
        }

        $query = new ListSuppliersQuery(1, 1000, $includeInactive);
        $envelope = $this->queryBus->dispatch($query);
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result['items'] ?? [], 200, [], ['groups' => ['supplier:read']]);
    }

    public function create(Request $request, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new CreateSupplierRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $activeFlag = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($activeFlag === null) {
            return $this->json(['error' => 'Invalid active flag'], 400);
        }

        $command = new CreateSupplierCommand(
            (string) $data['name'],
            $data['contactEmail'] ?? null,
            $data['contactPhone'] ?? null,
            $data['addressLine'] ?? null,
            $data['city'] ?? null,
            $data['country'] ?? null,
            $data['taxIdentifier'] ?? null,
            $data['notes'] ?? null,
            $activeFlag
        );
        
        $envelope = $this->commandBus->dispatch($command);
        $supplier = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($supplier, 201, [], ['groups' => ['supplier:read']]);
    }

    public function update(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid supplier id'], 400);
        }

        $data = json_decode($request->getContent(), true) ?: [];

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
                isset($data['active']) ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN) : null
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $supplier = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json($supplier, 200, [], ['groups' => ['supplier:read']]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    public function deactivate(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid supplier id'], 400);
        }

        try {
            $command = new DeactivateSupplierCommand((int) $id);
            $this->commandBus->dispatch($command);

            return new JsonResponse(null, 204);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }
}
