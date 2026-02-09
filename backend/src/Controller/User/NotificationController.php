<?php
namespace App\Controller\User;

use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Repository\NotificationLogRepository;
use App\Service\Auth\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Notification')]
class NotificationController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly SecurityService $security,
        private readonly NotificationLogRepository $notificationLogs,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[OA\Get(
        path: '/api/notifications',
        summary: 'List notifications',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(name: 'serviceDown', in: 'query', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            ),
            new OA\Response(response: 503, description: 'Service unavailable', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        if ($request->query->getBoolean('serviceDown', false)) {
            return $this->jsonErrorMessage(503, 'Notification service unavailable');
        }

        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->jsonErrorMessage(401, 'Unauthorized');
        }

        $limit = max(1, min(100, $request->query->getInt('limit', 20)));
        $logs = $this->notificationLogs->findInAppForUser($userId, $limit);
        if ($logs === []) {
            $logs = $this->seedTestNotifications($userId, $limit);
        }

        $notifications = [];
        foreach ($logs as $log) {
            $payload = $log->getPayload() ?? [];
            $notifications[] = [
                'id' => $log->getId(),
                'type' => $payload['type'] ?? $log->getType(),
                'title' => $payload['title'] ?? null,
                'message' => $payload['message'] ?? null,
                'link' => $payload['link'] ?? null,
                'createdAt' => $log->getSentAt()->format(DATE_ATOM),
            ];
        }

        return $this->json($notifications, 200);
    }

    #[OA\Post(
        path: '/api/notifications/test',
        summary: 'Trigger test notification',
        tags: ['Notifications'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['channel', 'target'],
                properties: [
                    new OA\Property(property: 'channel', type: 'string', enum: ['email', 'sms']),
                    new OA\Property(property: 'target', type: 'string'),
                    new OA\Property(property: 'message', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: 'Queued',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'channel', type: 'string'),
                        new OA\Property(property: 'target', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Missing input', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unsupported channel', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 503, description: 'Queue unavailable', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function triggerTest(Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        if ($request->headers->get('X-Queue-Status') === 'down') {
            return $this->jsonErrorMessage(503, 'Queue unavailable');
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        if (empty($payload['channel']) || empty($payload['target'])) {
            return $this->jsonErrorMessage(400, 'Missing channel or target');
        }

        $channel = $payload['channel'];
        if (!in_array($channel, ['email', 'sms'], true)) {
            return $this->jsonErrorMessage(422, 'Unsupported notification channel');
        }

        return $this->json([
            'status' => 'queued',
            'channel' => $channel,
            'target' => $payload['target'],
            'message' => $payload['message'] ?? 'Test notification',
        ], 202);
    }

    /**
     * @return \App\Entity\NotificationLog[]
     */
    private function seedTestNotifications(int $userId, int $limit): array
    {
        $user = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        if (!$user) {
            return [];
        }

        $templates = [
            [
                'type' => 'reservation_prepared',
                'title' => 'Reservation ready for pickup',
                'message' => 'Your reservation is ready for pickup. Please collect it within the next few days.',
                'link' => '/reservations',
            ],
            [
                'type' => 'announcement_published',
                'title' => 'New library announcement',
                'message' => 'A new announcement has been posted. Check the details in your dashboard.',
                'link' => '/announcements',
            ],
        ];

        $created = [];
        $count = 0;
        foreach ($templates as $template) {
            if ($count >= $limit) {
                break;
            }
            $fingerprint = substr(hash('sha256', $template['type'] . '|' . $userId . '|' . microtime(true) . '|' . random_int(0, PHP_INT_MAX)), 0, 64);
            $log = (new \App\Entity\NotificationLog())
                ->setUser($user)
                ->setType($template['type'])
                ->setChannel('in_app')
                ->setFingerprint($fingerprint)
                ->setPayload($template)
                ->setStatus('DELIVERED');

            $this->entityManager->persist($log);
            $created[] = $log;
            $count++;
        }

        $this->entityManager->flush();

        return $created;
    }
}


