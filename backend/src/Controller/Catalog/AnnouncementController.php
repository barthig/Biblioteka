<?php
namespace App\Controller\Catalog;

use App\Application\Command\Announcement\ArchiveAnnouncementCommand;
use App\Application\Command\Announcement\CreateAnnouncementCommand;
use App\Application\Command\Announcement\DeleteAnnouncementCommand;
use App\Application\Command\Announcement\PublishAnnouncementCommand;
use App\Application\Command\Announcement\UpdateAnnouncementCommand;
use App\Application\Query\Announcement\GetAnnouncementQuery;
use App\Application\Query\Announcement\ListAnnouncementsQuery;
use App\Application\Query\User\GetUserByIdQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Dto\ApiError;
use App\Entity\User;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Announcements')]
class AnnouncementController extends AbstractController
{
    use ExceptionHandlingTrait;
    use ValidationTrait;
    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
    ) {
    }
    
    #[OA\Get(
        path: '/api/announcements',
        summary: 'Pobiera listę ogłoszeń',
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Status ogłoszenia (draft/published/archived)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'homepage', in: 'query', description: 'Tylko ogłoszenia dla strony głównej', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Numer strony', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Liczba elementów na stronę', schema: new OA\Schema(type: 'integer', default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista ogłoszeń')
        ]
    )]
    public function list(Request $request, SecurityService $security): JsonResponse
    {
        try {
            $page = max(1, $request->query->getInt('page', 1));
            $limit = min(100, max(5, $request->query->getInt('limit', 20)));
            $status = $request->query->get('status');
            $homepageOnly = $request->query->getBoolean('homepage', false);

            $payload = $security->getJwtPayload($request);
            $user = null;
            $isLibrarian = false;
            $isAdmin = false;
            
            if ($payload && isset($payload['sub'])) {
                $envelope = $this->queryBus->dispatch(new GetUserByIdQuery((int) $payload['sub']));
                $user = $envelope->last(HandledStamp::class)?->getResult();
                if ($user) {
                    $roles = $user->getRoles();
                    $isLibrarian = in_array('ROLE_LIBRARIAN', $roles);
                    $isAdmin = in_array('ROLE_ADMIN', $roles);
                }
            }
            $isStaff = $isLibrarian || $isAdmin;

            $query = new ListAnnouncementsQuery($user, $isStaff, $status, $homepageOnly, $page, $limit);
            $envelope = $this->queryBus->dispatch($query);
            $result = $envelope->last(HandledStamp::class)?->getResult();

            $groups = $isStaff ? ['announcement:list', 'announcement:read'] : ['announcement:list'];
            return $this->json($result, 200, [], ['groups' => $groups]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonErrorMessage(500, 'Internal error: ' . $e->getMessage());
        }
    }

    #[OA\Get(
        path: '/api/announcements/{id}',
        summary: 'Pobiera szczegóły ogłoszenia',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID ogłoszenia', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Szczegóły ogłoszenia'),
            new OA\Response(response: 404, description: 'Nie znaleziono ogłoszenia')
        ]
    )]
    public function get(int $id, Request $request, SecurityService $security): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        $user = null;
        $isLibrarian = false;
        $isAdmin = false;
        
        if ($payload && isset($payload['sub'])) {
            $envelope = $this->queryBus->dispatch(new GetUserByIdQuery((int) $payload['sub']));
            $user = $envelope->last(HandledStamp::class)?->getResult();
            if ($user) {
                $roles = $user->getRoles();
                $isLibrarian = in_array('ROLE_LIBRARIAN', $roles);
                $isAdmin = in_array('ROLE_ADMIN', $roles);
            }
        }

        try {
            $query = new GetAnnouncementQuery($id, $user, $isLibrarian || $isAdmin);
            $envelope = $this->queryBus->dispatch($query);
            $announcement = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($announcement, 200, [], ['groups' => ['announcement:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonErrorMessage(404, $e->getMessage());
        }
    }

    #[OA\Post(
        path: '/api/announcements',
        summary: 'Tworzy nowe ogłoszenie',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Nowe ogłoszenie'),
                    new OA\Property(property: 'content', type: 'string', example: 'Treść ogłoszenia'),
                    new OA\Property(property: 'location', type: 'string', example: 'Sala spotkan, pietro 1', nullable: true),
                    new OA\Property(property: 'type', type: 'string', enum: ['info', 'warning', 'urgent', 'maintenance'], example: 'info'),
                    new OA\Property(property: 'isPinned', type: 'boolean', example: false),
                    new OA\Property(property: 'showOnHomepage', type: 'boolean', example: true),
                    new OA\Property(property: 'targetAudience', type: 'array', items: new OA\Items(type: 'string'), example: ['all']),
                    new OA\Property(property: 'expiresAt', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'eventAt', type: 'string', format: 'date-time', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ogłoszenie utworzone'),
            new OA\Response(response: 403, description: 'Brak uprawnień')
        ]
    )]
    public function create(Request $request, SecurityService $security): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonErrorMessage(401, 'Unauthorized');
        }

        $envelope = $this->queryBus->dispatch(new GetUserByIdQuery((int) $payload['sub']));
        $user = $envelope->last(HandledStamp::class)?->getResult();
        if (!$user) {
            return $this->jsonErrorMessage(404, 'User not found');
        }

        if (!in_array('ROLE_LIBRARIAN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->jsonErrorMessage(403, 'Access denied');
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['title']) || empty($data['content'])) {
            return $this->jsonErrorMessage(400, 'Title and content are required');
        }

        try {
            $command = new CreateAnnouncementCommand(
                $user->getId(),
                $data['title'],
                $data['content'],
                $data['location'] ?? null,
                $data['type'] ?? null,
                $data['isPinned'] ?? null,
                $data['showOnHomepage'] ?? null,
                $data['targetAudience'] ?? null,
                $data['expiresAt'] ?? null,
                $data['eventAt'] ?? null
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $announcement = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($announcement, 201, [], ['groups' => ['announcement:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonErrorMessage(400, $e->getMessage());
        }
    }

    #[OA\Put(
        path: '/api/announcements/{id}',
        summary: 'Aktualizuje ogłoszenie',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID ogłoszenia', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ogłoszenie zaktualizowane'),
            new OA\Response(response: 404, description: 'Nie znaleziono ogłoszenia')
        ]
    )]
    public function update(int $id, Request $request, SecurityService $security): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonErrorMessage(401, 'Unauthorized');
        }

        $envelope = $this->queryBus->dispatch(new GetUserByIdQuery((int) $payload['sub']));
        $user = $envelope->last(HandledStamp::class)?->getResult();
        if (!$user || (!in_array('ROLE_LIBRARIAN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))) {
            return $this->jsonErrorMessage(403, 'Access denied');
        }

        $data = json_decode($request->getContent(), true);
        
        try {
            $command = new UpdateAnnouncementCommand(
                $id,
                $data['title'] ?? null,
                $data['content'] ?? null,
                $data['location'] ?? null,
                $data['type'] ?? null,
                $data['isPinned'] ?? null,
                $data['showOnHomepage'] ?? null,
                $data['targetAudience'] ?? null,
                array_key_exists('expiresAt', $data) ? $data['expiresAt'] : 'NOT_SET',
                array_key_exists('eventAt', $data) ? $data['eventAt'] : 'NOT_SET'
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $announcement = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($announcement, 200, [], ['groups' => ['announcement:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonErrorMessage(404, $e->getMessage());
        }
    }

    #[OA\Post(
        path: '/api/announcements/{id}/publish',
        summary: 'Publikuje ogłoszenie',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID ogłoszenia', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ogłoszenie opublikowane')
        ]
    )]
    public function publish(int $id, Request $request, SecurityService $security): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonErrorMessage(401, 'Unauthorized');
        }

        $envelope = $this->queryBus->dispatch(new GetUserByIdQuery((int) $payload['sub']));
        $user = $envelope->last(HandledStamp::class)?->getResult();
        if (!$user || (!in_array('ROLE_LIBRARIAN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))) {
            return $this->jsonErrorMessage(403, 'Access denied');
        }

        try {
            $envelope = $this->commandBus->dispatch(new PublishAnnouncementCommand($id));
            $announcement = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($announcement, 200, [], ['groups' => ['announcement:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonErrorMessage(404, $e->getMessage());
        }
    }

    #[OA\Post(
        path: '/api/announcements/{id}/archive',
        summary: 'Archiwizuje ogłoszenie',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID ogłoszenia', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ogłoszenie zarchiwizowane')
        ]
    )]
    public function archive(int $id, Request $request, SecurityService $security): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonErrorMessage(401, 'Unauthorized');
        }

        $envelope = $this->queryBus->dispatch(new GetUserByIdQuery((int) $payload['sub']));
        $user = $envelope->last(HandledStamp::class)?->getResult();
        if (!$user || (!in_array('ROLE_LIBRARIAN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))) {
            return $this->jsonErrorMessage(403, 'Access denied');
        }

        try {
            $envelope = $this->commandBus->dispatch(new ArchiveAnnouncementCommand($id));
            $announcement = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($announcement, 200, [], ['groups' => ['announcement:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonErrorMessage(404, $e->getMessage());
        }
    }

    #[OA\Delete(
        path: '/api/announcements/{id}',
        summary: 'Usuwa ogłoszenie',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID ogłoszenia', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Ogłoszenie usunięte')
        ]
    )]
    public function delete(int $id, Request $request, SecurityService $security): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->jsonErrorMessage(401, 'Unauthorized');
        }

        $envelope = $this->queryBus->dispatch(new GetUserByIdQuery((int) $payload['sub']));
        $user = $envelope->last(HandledStamp::class)?->getResult();
        if (!$user || (!in_array('ROLE_LIBRARIAN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))) {
            return $this->jsonErrorMessage(403, 'Access denied');
        }

        try {
            $this->commandBus->dispatch(new DeleteAnnouncementCommand($id));
            return $this->json(null, 204);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonErrorMessage(404, $e->getMessage());
        }
    }
}



