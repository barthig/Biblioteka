<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use OpenApi\Attributes as OA;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Aggregated health check - queries all services in the distributed system.
 */
#[OA\Tag(name: 'Health')]
class DistributedHealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RedisClient $redis,
        private readonly LoggerInterface $logger,
        private readonly string $messengerTransportDsn = '',
        private readonly ?string $rabbitMqManagementUrl = null,
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

        try {
            $this->connection->executeQuery('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'error';
            $allOk = false;
        }

        try {
            $this->redis->ping();
            $checks['redis'] = 'ok';
        } catch (\Throwable $e) {
            $checks['redis'] = 'error';
            $allOk = false;
        }

        [$rabbitMqUrl, $rabbitMqUser, $rabbitMqPass] = $this->resolveRabbitMqHealthConfig();
        $checks['rabbitmq'] = $this->checkHttpHealth($rabbitMqUrl, $rabbitMqUser, $rabbitMqPass);
        if ($checks['rabbitmq'] !== 'ok') {
            $allOk = false;
        }

        $checks['notification_service'] = $this->checkHttpHealth('http://notification-service:8001/health');
        if ($checks['notification_service'] !== 'ok') {
            $allOk = false;
        }

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

    private function resolveRabbitMqHealthConfig(): array
    {
        $parts = parse_url($this->messengerTransportDsn);
        $host = $parts['host'] ?? 'rabbitmq';
        $user = $parts['user'] ?? null;
        $pass = $parts['pass'] ?? null;
        $url = $this->rabbitMqManagementUrl ?: sprintf('http://%s:15672/api/healthchecks/node', $host);

        return [$url, $user, $pass];
    }

    private function checkHttpHealth(string $url, ?string $user = null, ?string $pass = null): string
    {
        try {
            $headers = [];
            if ($user !== null && $pass !== null) {
                $headers[] = 'Authorization: Basic ' . base64_encode($user . ':' . $pass);
            }

            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                ],
            ]);
            $response = @file_get_contents($url, false, $ctx);
            if ($response !== false) {
                return 'ok';
            }

            return 'error';
        } catch (\Throwable $e) {
            $this->logger->warning('Distributed health HTTP probe failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return 'error';
        }
    }
}