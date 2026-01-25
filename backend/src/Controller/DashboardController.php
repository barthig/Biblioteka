<?php
namespace App\Controller;

use App\Application\Query\Dashboard\DashboardOverviewQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Dashboard')]
class DashboardController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly SecurityService $security,
        private readonly MessageBusInterface $queryBus
    ) {
    }

    #[OA\Get(
        path: '/api/dashboard',
        summary: 'Dashboard overview',
        tags: ['Dashboard'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function overview(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->jsonErrorMessage(401, 'Unauthorized');
        }

        $envelope = $this->queryBus->dispatch(new DashboardOverviewQuery($userId));
        $stats = $envelope->last(HandledStamp::class)?->getResult();

        return $this->jsonSuccess($stats);
    }
}
