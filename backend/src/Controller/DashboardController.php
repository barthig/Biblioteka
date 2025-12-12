<?php
namespace App\Controller;

use App\Application\Query\Dashboard\GetOverviewQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus
    ) {
    }

    public function overview(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetOverviewQuery());
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result, 200);
    }
}
