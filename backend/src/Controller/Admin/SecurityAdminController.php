<?php
namespace App\Controller\Admin;

use App\Repository\BackupRecordRepository;
use App\Service\BackupService;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class SecurityAdminController extends AbstractController
{
    public function __construct(private BackupService $backupService, private BackupRecordRepository $backups)
    {
    }

    public function createBackup(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
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

    public function listBackups(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
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

    public function viewLogs(Request $request, SecurityService $security, KernelInterface $kernel): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
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
