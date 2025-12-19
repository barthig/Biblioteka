<?php
namespace App\Controller;

use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class NotificationController extends AbstractController
{
    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        if ($request->query->getBoolean('serviceDown', false)) {
            return $this->json(['message' => 'Notification service unavailable'], 503);
        }

        $notifications = [
            ['id' => 1, 'type' => 'email', 'target' => 'reader@example.com', 'message' => 'Przypomnienie o zwrocie książki'],
            ['id' => 2, 'type' => 'sms', 'target' => '+48123123123', 'message' => 'Nowa rezerwacja do odebrania'],
        ];

        if (empty($notifications)) {
            return new JsonResponse(null, 204);
        }

        return $this->json($notifications, 200);
    }

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
