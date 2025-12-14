<?php
namespace App\Controller;

use App\Application\Query\Dashboard\DashboardOverviewQuery;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly SecurityService $security,
        private readonly MessageBusInterface $queryBus
    ) {
    }

    public function overview(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $envelope = $this->queryBus->dispatch(new DashboardOverviewQuery($userId));
        $stats = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($stats, 200);
    }
}
