<?php
namespace App\Controller;

use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/api/notifications', name: 'api_notifications_list', methods: ['GET'])]
    public function list(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if ($request->query->getBoolean('serviceDown', false)) {
            return $this->json(['error' => 'Notification service unavailable'], 503);
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

    #[Route('/api/notifications/test', name: 'api_notifications_test', methods: ['POST'])]
    public function triggerTest(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if ($request->headers->get('X-Queue-Status') === 'down') {
            return $this->json(['error' => 'Queue unavailable'], 503);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        if (empty($payload['channel']) || empty($payload['target'])) {
            return $this->json(['error' => 'Missing channel or target'], 400);
        }

        $channel = $payload['channel'];
        if (!in_array($channel, ['email', 'sms'], true)) {
            return $this->json(['error' => 'Unsupported notification channel'], 422);
        }

        return $this->json([
            'status' => 'queued',
            'channel' => $channel,
            'target' => $payload['target'],
            'message' => $payload['message'] ?? 'Test notification',
        ], 202);
    }
}
