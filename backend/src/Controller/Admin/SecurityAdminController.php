<?php
namespace App\Controller\Admin;

use App\Repository\BackupRecordRepository;
use App\Service\BackupService;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use OpenApi\Attributes as OA;

class SecurityAdminController extends AbstractController
{
    #[OA\Tag(name: 'Admin/SecurityAdmin')]
    public function __construct(private BackupService $backupService, private BackupRecordRepository $backups)
    {
    }

    #[OA\Post(
        path: '/api/admin/backups',
        summary: 'Create database backup',
        tags: ['Admin/SecurityAdmin'],
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function createBackup(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $payload = $security->getJwtPayload($request);
        $initiator = $payload['email'] ?? null;

        $record = $this->backupService->createBackup($initiator);

        return $this->json([
            'id' => $record->getId(),
            'fileName' => $record->getFileName(),
            'status' => $record->getStatus(),
            'createdAt' => $record->getCreatedAt()->format(DATE_ATOM),
        ], 201);
    }

    #[OA\Get(
        path: '/api/admin/backups',
        summary: 'List backups',
        tags: ['Admin/SecurityAdmin'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function listBackups(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $records = array_map(static function ($record): array {
            return [
                'id' => $record->getId(),
                'fileName' => $record->getFileName(),
                'status' => $record->getStatus(),
                'createdAt' => $record->getCreatedAt()->format(DATE_ATOM),
            ];
        }, $this->backups->findBy([], ['createdAt' => 'DESC']));

        return $this->json(['backups' => $records], 200);
    }

    #[OA\Get(
        path: '/api/admin/logs',
        summary: 'View application logs',
        tags: ['Admin/SecurityAdmin'],
        parameters: [new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 50))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function viewLogs(Request $request, SecurityService $security, KernelInterface $kernel): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

    $limit = max(1, min(200, $request->query->getInt('limit', 50)));
    $logPath = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'app.log';

        if (!file_exists($logPath)) {
            return $this->json(['entries' => []], 200);
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES) ?: [];
        $selected = array_slice($lines, -$limit);

        return $this->json([
            'entries' => $selected,
            'count' => count($selected),
        ], 200);
    }
}
