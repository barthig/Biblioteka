<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Aggregated health check — queries all services in the distributed system.
 */
#[OA\Tag(name: 'Health')]
class DistributedHealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RedisClient $redis,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[OA\Get(
        path: '/health/distributed',
        summary: 'Distributed system health check',
        tags: ['Health'],
        security: [],
        responses: [
            new OA\Response(response: 200, description: 'All services healthy'),
            new OA\Response(response: 503, description: 'One or more services degraded'),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $allOk = true;

        // 1. Own database
        try {
            $this->connection->executeQuery('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'error';
            $allOk = false;
        }

        // 2. Redis
        try {
            $this->redis->ping();
            $checks['redis'] = 'ok';
        } catch (\Throwable $e) {
            $checks['redis'] = 'error';
            $allOk = false;
        }

        // 3. RabbitMQ (via management API)
        $checks['rabbitmq'] = $this->checkHttpHealth('http://rabbitmq:15672/api/healthchecks/node', 'app', 'app');
        if ($checks['rabbitmq'] !== 'ok') {
            $allOk = false;
        }

        // 4. Notification Service
        $checks['notification_service'] = $this->checkHttpHealth('http://notification-service:8001/health');
        if ($checks['notification_service'] !== 'ok') {
            $allOk = false;
        }

        // 5. Recommendation Service
        $checks['recommendation_service'] = $this->checkHttpHealth('http://recommendation-service:8002/health');
        if ($checks['recommendation_service'] !== 'ok') {
            $allOk = false;
        }

        return $this->json([
            'status' => $allOk ? 'ok' : 'degraded',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'checks' => $checks,
        ], $allOk ? 200 : 503);
    }

    private function checkHttpHealth(string $url, ?string $user = null, ?string $pass = null): string
    {
        try {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'method' => 'GET',
                    'header' => $user ? 'Authorization: Basic ' . base64_encode("$user:$pass") : '',
                ],
            ]);
            $response = @file_get_contents($url, false, $ctx);
            if ($response !== false) {
                return 'ok';
            }
            return 'error';
        } catch (\Throwable $e) {
            return 'error';
        }
    }
}
