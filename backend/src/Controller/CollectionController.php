<?php
namespace App\Controller;

use App\Entity\BookCollection;
use App\Repository\BookRepository;
use App\Repository\CollectionRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CollectionController extends AbstractController
{
    public function __construct(
        private readonly SecurityService $security,
        private readonly EntityManagerInterface $em,
        private readonly CollectionRepository $collectionRepo,
        private readonly BookRepository $bookRepo
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
            return $this->json(['error' => 'Collection not found'], 404);
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
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $user = $this->em->getRepository(\App\Entity\User::class)->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || trim($data['name']) === '') {
            return $this->json(['error' => 'Collection name is required'], 400);
        }

        $collection = new BookCollection();
        $collection->setName($data['name'])
            ->setDescription($data['description'] ?? null)
            ->setCuratedBy($user)
            ->setFeatured($data['featured'] ?? false)
            ->setDisplayOrder($data['displayOrder'] ?? 0);

        if (isset($data['bookIds']) && is_array($data['bookIds'])) {
            foreach ($data['bookIds'] as $bookId) {
                $book = $this->bookRepo->find($bookId);
                if ($book) {
                    $collection->addBook($book);
                }
            }
        }

        $this->em->persist($collection);
        $this->em->flush();

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
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $collection = $this->collectionRepo->find($id);
        if (!$collection) {
            return $this->json(['error' => 'Collection not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $collection->setName($data['name']);
        }
        if (isset($data['description'])) {
            $collection->setDescription($data['description']);
        }
        if (isset($data['featured'])) {
            $collection->setFeatured($data['featured']);
        }
        if (isset($data['displayOrder'])) {
            $collection->setDisplayOrder($data['displayOrder']);
        }

        if (isset($data['bookIds']) && is_array($data['bookIds'])) {
            foreach ($collection->getBooks()->toArray() as $book) {
                $collection->removeBook($book);
            }
            foreach ($data['bookIds'] as $bookId) {
                $book = $this->bookRepo->find($bookId);
                if ($book) {
                    $collection->addBook($book);
                }
            }
        }

        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Collection updated']);
    }

    public function delete(int $id, Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if (!$userId || !$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $collection = $this->collectionRepo->find($id);
        if (!$collection) {
            return $this->json(['error' => 'Collection not found'], 404);
        }

        $this->em->remove($collection);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Collection deleted']);
    }
}
