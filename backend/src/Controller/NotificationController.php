<?php
namespace App\Controller;

use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Notification')]
class NotificationController extends AbstractController
{
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
    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if ($request->query->getBoolean('serviceDown', false)) {
            return $this->json(['message' => 'Notification service unavailable'], 503);
        }

        $now = new \DateTimeImmutable();
        $notifications = [
            [
                'id' => 1,
                'type' => 'email',
                'target' => 'reader@example.com',
                'title' => 'Przypomnienie o zwrocie',
                'message' => 'Przypomnienie o zwrocie ksiazki',
                'createdAt' => $now->modify('-90 minutes')->format(DATE_ATOM),
            ],
            [
                'id' => 2,
                'type' => 'sms',
                'target' => '+48123123123',
                'title' => 'Rezerwacja gotowa',
                'message' => 'Nowa rezerwacja do odebrania',
                'createdAt' => $now->modify('-25 minutes')->format(DATE_ATOM),
            ],
        ];

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
    public function triggerTest(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        if ($request->headers->get('X-Queue-Status') === 'down') {
            return $this->json(['message' => 'Queue unavailable'], 503);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        if (empty($payload['channel']) || empty($payload['target'])) {
            return $this->json(['message' => 'Missing channel or target'], 400);
        }

        $channel = $payload['channel'];
        if (!in_array($channel, ['email', 'sms'], true)) {
            return $this->json(['message' => 'Unsupported notification channel'], 422);
        }

        return $this->json([
            'status' => 'queued',
            'channel' => $channel,
            'target' => $payload['target'],
            'message' => $payload['message'] ?? 'Test notification',
        ], 202);
    }
}
