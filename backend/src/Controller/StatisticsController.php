<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Query\Statistics\GetLibraryStatisticsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Statistics')]
class StatisticsController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly MessageBusInterface $queryBus
    ) {}

    #[IsGranted('ROLE_LIBRARIAN')]
    #[OA\Get(
        path: '/api/statistics/dashboard',
        summary: 'Get dashboard statistics (Librarian)',
        description: 'Returns key metrics for librarian dashboard including loans, reservations, users, and popular books',
        tags: ['Statistics'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard statistics',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'activeLoans', type: 'integer', example: 142),
                        new OA\Property(property: 'overdueLoans', type: 'integer', example: 8),
                        new OA\Property(property: 'pendingReservations', type: 'integer', example: 23),
                        new OA\Property(property: 'totalUsers', type: 'integer', example: 456),
                        new OA\Property(property: 'totalBooks', type: 'integer', example: 1250),
                        new OA\Property(property: 'availableCopies', type: 'integer', example: 3420),
                        new OA\Property(
                            property: 'popularBooks',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'title', type: 'string'),
                                    new OA\Property(property: 'borrowCount', type: 'integer')
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'recentActivity',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'action', type: 'string'),
                                    new OA\Property(property: 'timestamp', type: 'string', format: 'date-time')
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden - Librarian role required')
        ]
    )]
    public function dashboard(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetLibraryStatisticsQuery());
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result);
    }
}
