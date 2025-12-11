<?php
namespace App\Controller;

use App\Entity\AuditLog;
use App\Repository\AuditLogRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

class AuditLogController extends AbstractController
{
    #[OA\Get(
        path: '/api/audit-logs',
        summary: 'Lista logów audytu',
        description: 'Zwraca historię operacji w systemie. Wymaga roli LIBRARIAN.',
        tags: ['Audit'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 50, maximum: 100)),
            new OA\Parameter(name: 'entityType', in: 'query', description: 'Typ encji (Book, User, Loan, etc.)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'action', in: 'query', description: 'Akcja (CREATE, UPDATE, DELETE, LOGIN, etc.)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'userId', in: 'query', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista logów audytu',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'entityType', type: 'string'),
                                    new OA\Property(property: 'entityId', type: 'integer'),
                                    new OA\Property(property: 'action', type: 'string'),
                                    new OA\Property(property: 'user', type: 'object'),
                                    new OA\Property(property: 'ipAddress', type: 'string'),
                                    new OA\Property(property: 'oldValues', type: 'string'),
                                    new OA\Property(property: 'newValues', type: 'string'),
                                    new OA\Property(property: 'description', type: 'string'),
                                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time')
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'page', type: 'integer'),
                                new OA\Property(property: 'limit', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                                new OA\Property(property: 'totalPages', type: 'integer')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Brak uprawnień')
        ]
    )]
    public function list(Request $request, AuditLogRepository $repo, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(10, $request->query->getInt('limit', 50)));

        $filters = [];
        if ($request->query->has('entityType')) {
            $filters['entityType'] = $request->query->get('entityType');
        }
        if ($request->query->has('action')) {
            $filters['action'] = $request->query->get('action');
        }
        if ($request->query->has('userId')) {
            $filters['userId'] = $request->query->getInt('userId');
        }

        $result = $repo->findWithPagination($page, $limit, $filters);

        return $this->json($result, 200, [], ['groups' => ['audit:read']]);
    }

    #[OA\Get(
        path: '/api/audit-logs/entity/{entityType}/{entityId}',
        summary: 'Historia zmian encji',
        description: 'Zwraca wszystkie operacje dla konkretnej encji',
        tags: ['Audit'],
        parameters: [
            new OA\Parameter(name: 'entityType', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'entityId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Historia encji'),
            new OA\Response(response: 403, description: 'Brak uprawnień')
        ]
    )]
    public function entityHistory(string $entityType, int $entityId, AuditLogRepository $repo, SecurityService $security, Request $request): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $logs = $repo->findByEntity($entityType, $entityId);

        return $this->json($logs, 200, [], ['groups' => ['audit:read']]);
    }
}
