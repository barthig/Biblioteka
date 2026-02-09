<?php
declare(strict_types=1);
namespace App\Controller\Catalog;

use App\Application\Command\Catalog\ImportCatalogCommand;
use App\Application\Query\Catalog\ExportCatalogQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'CatalogAdmin')]
class CatalogAdminController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    #[OA\Get(
        path: '/api/admin/catalog/export',
        summary: 'Export catalog',
        tags: ['Catalog'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function export(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $envelope = $this->queryBus->dispatch(new ExportCatalogQuery());
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result);
    }

    #[OA\Post(
        path: '/api/admin/catalog/import',
        summary: 'Import catalog',
        tags: ['Catalog'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(type: 'object')
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Invalid payload', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function import(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            return $this->jsonErrorMessage(400, 'Invalid payload structure');
        }

        $envelope = $this->commandBus->dispatch(new ImportCatalogCommand($data['items']));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result);
    }
}


