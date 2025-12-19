<?php
namespace App\Controller;

use App\Application\Command\Collection\CreateCollectionCommand;
use App\Application\Command\Collection\UpdateCollectionCommand;
use App\Application\Command\Collection\DeleteCollectionCommand;
use App\Entity\BookCollection;
use App\Repository\CollectionRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class CollectionController extends AbstractController
{
    public function __construct(
        private readonly SecurityService $security,
        private readonly CollectionRepository $collectionRepo,
        private readonly MessageBusInterface $commandBus
    ) {}

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
                    'coverUrl' => $book->getCoverUrl(),
                ], $c->getBooks()->toArray()),
                'curatedBy' => $c->getCuratedBy()->getName(),
                'createdAt' => $c->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $collections)
        ]);
    }

    public function get(int $id): JsonResponse
    {
        $collection = $this->collectionRepo->find($id);
        if (!$collection) {
            return $this->json(['message' => 'Collection not found'], 404);
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
                'coverUrl' => $book->getCoverUrl(),
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
            return $this->json(['message' => 'Forbidden'], 403);
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

    public function update(int $id, Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId || !$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
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

        return $this->json(['success' => true, 'message' => 'Collection updated']);
    }

    public function delete(int $id, Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId || !$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $this->commandBus->dispatch(new DeleteCollectionCommand($id));

        return new JsonResponse(null, 204);
    }
}
