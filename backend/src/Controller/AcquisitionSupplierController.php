<?php
namespace App\Controller;

use App\Application\Command\Acquisition\CreateSupplierCommand;
use App\Application\Command\Acquisition\DeactivateSupplierCommand;
use App\Application\Command\Acquisition\UpdateSupplierCommand;
use App\Application\Query\Acquisition\ListSuppliersQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Request\CreateSupplierRequest;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'AcquisitionSupplier')]
class AcquisitionSupplierController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;
    
    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
    ) {
    }
    
    #[OA\Get(
        path: '/api/suppliers',
        summary: 'List acquisition suppliers',
        tags: ['AcquisitionSupplier'],
        parameters: [new OA\Parameter(name: 'active', in: 'query', schema: new OA\Schema(type: 'boolean', nullable: true))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
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

    #[OA\Post(
        path: '/api/suppliers',
        summary: 'Create acquisition supplier',
        tags: ['AcquisitionSupplier'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'active', type: 'boolean', nullable: true),
                    new OA\Property(property: 'contactEmail', type: 'string', nullable: true),
                    new OA\Property(property: 'contactPhone', type: 'string', nullable: true),
                    new OA\Property(property: 'addressLine', type: 'string', nullable: true),
                    new OA\Property(property: 'city', type: 'string', nullable: true),
                    new OA\Property(property: 'country', type: 'string', nullable: true),
                    new OA\Property(property: 'taxIdentifier', type: 'string', nullable: true),
                    new OA\Property(property: 'notes', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(Request $request, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        $dto = $this->mapArrayToDto($data, new CreateSupplierRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $activeFlag = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($activeFlag === null) {
            return $this->jsonError(ApiError::badRequest('Invalid active flag'));
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

    #[OA\Put(
        path: '/api/suppliers/{id}',
        summary: 'Update acquisition supplier',
        tags: ['AcquisitionSupplier'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid supplier id'));
        }

        $data = json_decode($request->getContent(), true) ?: [];

        $activeValue = null;
        if (array_key_exists('active', $data)) {
            $activeValue = filter_var($data['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($activeValue === null) {
                return $this->jsonError(ApiError::badRequest('Invalid active flag'));
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
            return $this->jsonError(ApiError::notFound('Supplier'));
        }
    }

    #[OA\Delete(
        path: '/api/suppliers/{id}',
        summary: 'Deactivate acquisition supplier',
        tags: ['AcquisitionSupplier'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function deactivate(string $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid supplier id'));
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
            return $this->jsonError(ApiError::notFound('Supplier'));
        }
    }
}
