<?php
namespace App\Controller;

use App\Entity\Announcement;
use App\Entity\User;
use App\Repository\AnnouncementRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api')]
#[OA\Tag(name: 'Announcements')]
class AnnouncementController extends AbstractController
{
    #[Route('/announcements', methods: ['GET'])]
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
    public function list(
        Request $request,
        AnnouncementRepository $announcementRepository,
        SecurityService $security,
        ManagerRegistry $doctrine
    ): JsonResponse {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(5, $request->query->getInt('limit', 20)));
        $offset = ($page - 1) * $limit;

        $payload = $security->getJwtPayload($request);
        $user = null;
        $isLibrarian = false;
        
        if ($payload && isset($payload['sub'])) {
            $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
            if ($user) {
                $isLibrarian = in_array('ROLE_LIBRARIAN', $user->getRoles());
            }
        }

        // Dla bibliotekarzy - wszystkie ogłoszenia z filtrowaniem
        if ($isLibrarian) {
            $status = $request->query->get('status');
            
            if ($status) {
                $announcements = $announcementRepository->findByStatus($status);
            } else {
                $announcements = $announcementRepository->findAllWithCreator();
            }

            $total = count($announcements);
            $announcements = array_slice($announcements, $offset, $limit);

            return $this->json([
                'data' => $announcements,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => $total > 0 ? (int)ceil($total / $limit) : 0
                ]
            ], 200, [], ['groups' => ['announcement:list', 'announcement:read']]);
        }

        // Dla zwykłych użytkowników - tylko aktywne
        $homepageOnly = $request->query->getBoolean('homepage', false);
        
        if ($homepageOnly) {
            $announcements = $announcementRepository->findForHomepage($user, $limit);
            $total = count($announcements);
        } else {
            $announcements = $announcementRepository->findActiveForUser($user);
            $total = count($announcements);
            $announcements = array_slice($announcements, $offset, $limit);
        }

        return $this->json([
            'data' => array_values($announcements),
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int)ceil($total / $limit) : 0
            ]
        ], 200, [], ['groups' => ['announcement:list']]);
    }

    #[Route('/announcements/{id}', methods: ['GET'])]
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
    public function show(
        int $id,
        Request $request,
        AnnouncementRepository $announcementRepository,
        SecurityService $security,
        ManagerRegistry $doctrine
    ): JsonResponse {
        $announcement = $announcementRepository->find($id);
        
        if (!$announcement) {
            return $this->json(['error' => 'Announcement not found'], 404);
        }

        $payload = $security->getJwtPayload($request);
        $user = null;
        $isLibrarian = false;
        
        if ($payload && isset($payload['sub'])) {
            $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
            if ($user) {
                $isLibrarian = in_array('ROLE_LIBRARIAN', $user->getRoles());
            }
        }
        
        // Bibliotekarze widzą wszystko
        if ($isLibrarian) {
            return $this->json($announcement, 200, [], ['groups' => ['announcement:read']]);
        }

        // Zwykli użytkownicy - tylko aktywne i widoczne dla nich
        
        if (!$announcement->isVisibleForUser($user)) {
            return $this->json(['error' => 'Announcement not found'], 404);
        }

        return $this->json($announcement, 200, [], ['groups' => ['announcement:read']]);
    }

    #[Route('/announcements', methods: ['POST'])]
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
                    new OA\Property(property: 'type', type: 'string', enum: ['info', 'warning', 'urgent', 'maintenance'], example: 'info'),
                    new OA\Property(property: 'isPinned', type: 'boolean', example: false),
                    new OA\Property(property: 'showOnHomepage', type: 'boolean', example: true),
                    new OA\Property(property: 'targetAudience', type: 'array', items: new OA\Items(type: 'string'), example: ['all']),
                    new OA\Property(property: 'expiresAt', type: 'string', format: 'date-time', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ogłoszenie utworzone'),
            new OA\Response(response: 403, description: 'Brak uprawnień')
        ]
    )]
    public function create(
        Request $request,
        ManagerRegistry $doctrine,
        SecurityService $security
    ): JsonResponse {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        if (!in_array('ROLE_LIBRARIAN', $user->getRoles())) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['title']) || empty($data['content'])) {
            return $this->json(['error' => 'Title and content are required'], 400);
        }

        $announcement = new Announcement();
        $announcement->setTitle($data['title']);
        $announcement->setContent($data['content']);
        if ($user instanceof \App\Entity\User) {
            $announcement->setCreatedBy($user);
        }

        if (isset($data['type'])) {
            $announcement->setType($data['type']);
        }

        if (isset($data['isPinned'])) {
            $announcement->setIsPinned((bool)$data['isPinned']);
        }

        if (isset($data['showOnHomepage'])) {
            $announcement->setShowOnHomepage((bool)$data['showOnHomepage']);
        }

        if (isset($data['targetAudience'])) {
            $announcement->setTargetAudience($data['targetAudience']);
        }

        if (isset($data['expiresAt'])) {
            $announcement->setExpiresAt(new \DateTimeImmutable($data['expiresAt']));
        }

        $em = $doctrine->getManager();
        $em->persist($announcement);
        $em->flush();

        return $this->json($announcement, 201, [], ['groups' => ['announcement:read']]);
    }

    #[Route('/announcements/{id}', methods: ['PUT', 'PATCH'])]
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
    public function update(
        int $id,
        Request $request,
        ManagerRegistry $doctrine,
        AnnouncementRepository $announcementRepository,
        SecurityService $security
    ): JsonResponse {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
        if (!$user || !in_array('ROLE_LIBRARIAN', $user->getRoles())) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $announcement = $announcementRepository->find($id);
        
        if (!$announcement) {
            return $this->json(['error' => 'Announcement not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $announcement->setTitle($data['title']);
        }

        if (isset($data['content'])) {
            $announcement->setContent($data['content']);
        }

        if (isset($data['type'])) {
            $announcement->setType($data['type']);
        }

        if (isset($data['isPinned'])) {
            $announcement->setIsPinned((bool)$data['isPinned']);
        }

        if (isset($data['showOnHomepage'])) {
            $announcement->setShowOnHomepage((bool)$data['showOnHomepage']);
        }

        if (isset($data['targetAudience'])) {
            $announcement->setTargetAudience($data['targetAudience']);
        }

        if (array_key_exists('expiresAt', $data)) {
            $announcement->setExpiresAt($data['expiresAt'] ? new \DateTimeImmutable($data['expiresAt']) : null);
        }

        $em = $doctrine->getManager();
        $em->flush();

        return $this->json($announcement, 200, [], ['groups' => ['announcement:read']]);
    }

    #[Route('/announcements/{id}/publish', methods: ['POST'])]
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
    public function publish(
        int $id,
        Request $request,
        ManagerRegistry $doctrine,
        AnnouncementRepository $announcementRepository,
        SecurityService $security
    ): JsonResponse {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
        if (!$user || !in_array('ROLE_LIBRARIAN', $user->getRoles())) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $announcement = $announcementRepository->find($id);
        
        if (!$announcement) {
            return $this->json(['error' => 'Announcement not found'], 404);
        }

        $announcement->publish();

        $em = $doctrine->getManager();
        $em->flush();

        return $this->json($announcement, 200, [], ['groups' => ['announcement:read']]);
    }

    #[Route('/announcements/{id}/archive', methods: ['POST'])]
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
    public function archive(
        int $id,
        Request $request,
        ManagerRegistry $doctrine,
        AnnouncementRepository $announcementRepository,
        SecurityService $security
    ): JsonResponse {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
        if (!$user || !in_array('ROLE_LIBRARIAN', $user->getRoles())) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $announcement = $announcementRepository->find($id);
        
        if (!$announcement) {
            return $this->json(['error' => 'Announcement not found'], 404);
        }

        $announcement->archive();

        $em = $doctrine->getManager();
        $em->flush();

        return $this->json($announcement, 200, [], ['groups' => ['announcement:read']]);
    }

    #[Route('/announcements/{id}', methods: ['DELETE'])]
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
    public function delete(
        int $id,
        Request $request,
        ManagerRegistry $doctrine,
        AnnouncementRepository $announcementRepository,
        SecurityService $security
    ): JsonResponse {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
        if (!$user || !in_array('ROLE_LIBRARIAN', $user->getRoles())) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $announcement = $announcementRepository->find($id);
        
        if (!$announcement) {
            return $this->json(['error' => 'Announcement not found'], 404);
        }

        $em = $doctrine->getManager();
        $em->remove($announcement);
        $em->flush();

        return $this->json(null, 204);
    }

    private function getDoctrine(): ManagerRegistry
    {
        return $this->container->get('doctrine');
    }
}
