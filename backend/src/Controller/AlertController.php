<?php
namespace App\Controller;

use App\Application\Query\Alert\UserAlertsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use OpenApi\Attributes as OA;

class AlertController extends AbstractController
{
    #[OA\Tag(name: 'Alert')]
    public function __construct(
        private readonly SecurityService $security,
        private readonly MessageBusInterface $queryBus
    ) {
    }

    #[OA\Get(
        path: '/api/alerts',
        summary: 'Alerty użytkownika',
        description: 'Zwraca listę aktywnych alertów dla zalogowanego użytkownika',
        tags: ['Alerts'],
        responses: [
            new OA\Response(response: 200, description: 'Lista alertów', content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))),
            new OA\Response(response: 401, description: 'Nieautoryzowany', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
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

    #[OA\Get(
        path: '/api/library-hours',
        summary: 'Godziny otwarcia biblioteki',
        description: 'Zwraca godziny otwarcia biblioteki dla każdego dnia tygodnia',
        tags: ['Alerts'],
        responses: [
            new OA\Response(response: 200, description: 'Godziny otwarcia', content: new OA\JsonContent(type: 'object'))
        ]
    )]
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
