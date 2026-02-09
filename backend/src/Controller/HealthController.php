<?php
namespace App\Controller;

use App\Dto\ApiError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\DBAL\Connection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Health')]
class HealthController extends AbstractController
{
    public function __construct(private Connection $connection)
    {
    }

    #[OA\Get(
        path: '/health',
        summary: 'Health check',
        tags: ['Health'],
        security: [],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(
                response: 500,
                description: 'Degraded',
                content: new OA\JsonContent(type: 'object')
            ),
        ]
    )]
    public function health(): JsonResponse
    {
        $dbStatus = 'ok';
        try {
            $this->connection->executeQuery('SELECT 1');
        } catch (\Throwable $e) {
            $dbStatus = 'error';
        }

        $status = $dbStatus === 'ok' ? 'ok' : 'degraded';

        return $this->json([
            'status' => $status,
            'message' => 'Biblioteka API is running',
            'checks' => [
                'database' => $dbStatus,
            ],
        ], $dbStatus === 'ok' ? 200 : 500);
    }
}
