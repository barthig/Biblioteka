<?php
namespace App\Controller;

use App\Application\Query\Alert\UserAlertsQuery;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class AlertController extends AbstractController
{
    public function __construct(
        private readonly SecurityService $security,
        private readonly MessageBusInterface $queryBus
    ) {
    }

    public function getAlerts(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $envelope = $this->queryBus->dispatch(new UserAlertsQuery($userId));
        $alerts = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json($alerts);
    }

    public function getLibraryHours(Request $request): JsonResponse
    {
        $hours = [
            'Poniedziałek' => '9:00 - 19:00',
            'Wtorek' => '9:00 - 19:00',
            'Środa' => '9:00 - 19:00',
            'Czwartek' => '9:00 - 19:00',
            'Piątek' => '9:00 - 17:00',
            'Sobota' => '10:00 - 15:00',
            'Niedziela' => 'Nieczynne'
        ];

        return $this->json($hours);
    }
}
