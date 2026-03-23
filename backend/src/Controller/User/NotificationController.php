<?php
declare(strict_types=1);
namespace App\Controller\User;

use App\Application\Command\Notification\TriggerTestNotificationCommand;
use App\Application\Query\Notification\ListNotificationsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Service\Auth\SecurityService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[OA\Tag(name: 'Notification')]
class NotificationController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly SecurityService $security,
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
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
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId) {
            return $this->jsonErrorMessage(401, 'Unauthorized');
        }

        $query = new ListNotificationsQuery(
            userId: $userId,
            limit: max(1, min(100, $request->query->getInt('limit', 20))),
            serviceDown: $request->query->getBoolean('serviceDown', false)
        );

        try {
            $envelope = $this->queryBus->dispatch($query);
            $notifications = $envelope->last(HandledStamp::class)?->getResult() ?? [];
            return $this->json($notifications, 200);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }

            return $this->jsonError(ApiError::internalError($e->getMessage()));
        }
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

        $payload = json_decode($request->getContent(), true) ?? [];
        if (empty($payload['channel']) || empty($payload['target'])) {
            return $this->jsonErrorMessage(400, 'Missing channel or target');
        }

        $channel = (string) $payload['channel'];
        if (!in_array($channel, ['email', 'sms'], true)) {
            return $this->jsonErrorMessage(422, 'Unsupported notification channel');
        }

        $command = new TriggerTestNotificationCommand(
            requestedByUserId: $this->security->getCurrentUserId($request) ?? 0,
            channel: $channel,
            target: (string) $payload['target'],
            message: (string) ($payload['message'] ?? 'Test notification'),
            queueAvailable: $request->headers->get('X-Queue-Status') !== 'down'
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $result = $envelope->last(HandledStamp::class)?->getResult() ?? [];
            return $this->json($result, 202);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }

            return $this->jsonError(ApiError::internalError($e->getMessage()));
        }
    }
}
