<?php
namespace App\Controller;

use App\Application\Command\Catalog\ImportCatalogCommand;
use App\Application\Query\Catalog\ExportCatalogQuery;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class CatalogAdminController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    public function export(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $envelope = $this->queryBus->dispatch(new ExportCatalogQuery());
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result);
    }

    public function import(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            return $this->json(['message' => 'Invalid payload structure'], 400);
        }

        $envelope = $this->commandBus->dispatch(new ImportCatalogCommand($data['items']));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result);
    }
}
