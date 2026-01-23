<?php
namespace App\Controller;

use App\Application\Command\Collection\CreateCollectionCommand;
use App\Application\Command\Collection\UpdateCollectionCommand;
use App\Application\Command\Collection\DeleteCollectionCommand;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Entity\BookCollection;
use App\Repository\CollectionRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Collection')]
class CollectionController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly SecurityService $security,
        private readonly CollectionRepository $collectionRepo,
        private readonly MessageBusInterface $commandBus
    ) {}

    #[OA\Get(
        path: '/api/collections',
        summary: 'List collections',
        tags: ['Collections'],
        parameters: [
            new OA\Parameter(name: 'featured', in: 'query', schema: new OA\Schema(type: 'boolean', default: false)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'collections',
                            type: 'array',
                            items: new OA\Items(type: 'object')
                        )
                    ]
                )
            ),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $featured = $request->query->getBoolean('featured', false);

        $collections = $featured 
            ? $this->collectionRepo->findFeatured()
            : $this->collectionRepo->findAllOrdered();

        return $this->json([
            'collections' => array_map(fn(BookCollection $c) => [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'description' => $c->getDescription(),
                'featured' => $c->isFeatured(),
                'displayOrder' => $c->getDisplayOrder(),
                'bookCount' => $c->getBooks()->count(),
                'books' => array_map(fn($book) => [
                    'id' => $book->getId(),
                    'title' => $book->getTitle(),
                    'author' => $book->getAuthor()?->getName(),
                    'coverUrl' => method_exists($book, 'getCoverUrl') ? $book->getCoverUrl() : null,
                ], $c->getBooks()->toArray()),
                'curatedBy' => $c->getCuratedBy()?->getName(),
                'createdAt' => $c->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $collections)
        ]);
    }

    #[OA\Get(
        path: '/api/collections/{id}',
        summary: 'Get collection by id',
        tags: ['Collections'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function get(int $id): JsonResponse
    {
        $collection = $this->collectionRepo->find($id);
        if (!$collection) {
            return $this->jsonErrorMessage(404, 'Collection not found');
        }

        return $this->json([
            'id' => $collection->getId(),
            'name' => $collection->getName(),
            'description' => $collection->getDescription(),
            'featured' => $collection->isFeatured(),
            'displayOrder' => $collection->getDisplayOrder(),
            'books' => array_map(fn($book) => [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'author' => $book->getAuthor()?->getName(),
                'coverUrl' => method_exists($book, 'getCoverUrl') ? $book->getCoverUrl() : null,
                'isbn' => $book->getIsbn(),
            ], $collection->getBooks()->toArray()),
            'curatedBy' => [
                'id' => $collection->getCuratedBy()->getId(),
                'name' => $collection->getCuratedBy()->getName(),
            ],
            'createdAt' => $collection->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $collection->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId || !$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $data = json_decode($request->getContent(), true);
        $envelope = $this->commandBus->dispatch(new CreateCollectionCommand(
            userId: $userId,
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
            featured: $data['featured'] ?? false,
            displayOrder: $data['displayOrder'] ?? 0,
            bookIds: isset($data['bookIds']) && is_array($data['bookIds']) ? $data['bookIds'] : []
        ));
        $collection = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json([
            'success' => true,
            'collection' => [
                'id' => $collection->getId(),
                'name' => $collection->getName(),
                'description' => $collection->getDescription(),
                'bookCount' => $collection->getBooks()->count(),
            ]
        ], 201);
    }
    #[OA\Post(
        path: '/api/collections',
        summary: 'Utwórz kolekcję',
        description: 'Tworzy nową kolekcję książek. Wymaga roli LIBRARIAN.',
        tags: ['Collections'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'featured', type: 'boolean'),
                    new OA\Property(property: 'displayOrder', type: 'integer'),
                    new OA\Property(property: 'bookIds', type: 'array', items: new OA\Items(type: 'integer'))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Kolekcja utworzona', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]    public function update(int $id, Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId || !$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $data = json_decode($request->getContent(), true);
        $this->commandBus->dispatch(new UpdateCollectionCommand(
            collectionId: $id,
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            featured: array_key_exists('featured', $data) ? (bool) $data['featured'] : null,
            displayOrder: array_key_exists('displayOrder', $data) ? (int) $data['displayOrder'] : null,
            bookIds: isset($data['bookIds']) && is_array($data['bookIds']) ? $data['bookIds'] : null
        ));

        return $this->jsonSuccess(['message' => 'Collection updated']);
    }
    #[OA\Delete(
        path: '/api/collections/{id}',
        summary: 'Usuń kolekcję',
        description: 'Usuwa kolekcję. Wymaga roli ADMIN.',
        tags: ['Collections'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Kolekcja usunięta'),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]    public function delete(int $id, Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId || !$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $this->commandBus->dispatch(new DeleteCollectionCommand($id));

        return new JsonResponse(null, 204);
    }
}
