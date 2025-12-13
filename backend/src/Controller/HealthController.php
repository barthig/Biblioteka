<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\DBAL\Connection;

class HealthController extends AbstractController
{
    public function __construct(private Connection $connection)
    {
    }

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
