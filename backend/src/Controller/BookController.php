<?php
namespace App\Controller;

use App\Entity\Book;
use App\Entity\Favorite;
use App\Entity\User;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BookCopy;

class BookController extends AbstractController
{
    public function list(Request $request, BookRepository $repo, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        $filters = [
            'q' => $request->query->get('q'),
            'authorId' => $request->query->has('authorId') ? $request->query->getInt('authorId') : null,
            'categoryId' => $request->query->has('categoryId') ? $request->query->getInt('categoryId') : null,
            'publisher' => $request->query->get('publisher'),
            'resourceType' => $request->query->get('resourceType'),
            'signature' => $request->query->get('signature'),
            'yearFrom' => $request->query->has('yearFrom') ? $request->query->getInt('yearFrom') : null,
            'yearTo' => $request->query->has('yearTo') ? $request->query->getInt('yearTo') : null,
        ];

        if ($request->query->has('available')) {
            $filters['available'] = $request->query->get('available');
        }

        $books = $repo->searchPublic($filters);

        $payload = $security->getJwtPayload($request);
        if ($payload && isset($payload['sub'])) {
            $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
            if ($user) {
                /** @var \App\Repository\FavoriteRepository $favoriteRepo */
                $favoriteRepo = $doctrine->getRepository(Favorite::class);
                $favoriteBookIds = $favoriteRepo->getBookIdsForUser($user);
                if (!empty($favoriteBookIds)) {
                    $favoriteLookup = array_flip($favoriteBookIds);
                    foreach ($books as $book) {
                        if ($book instanceof Book && $book->getId() !== null && isset($favoriteLookup[$book->getId()])) {
                            $book->setIsFavorite(true);
                        }
                    }
                }
            }
        }

        return $this->json($books, 200, [], ['groups' => ['book:read']]);
    }

    public function filters(BookRepository $repo): JsonResponse
    {
        return $this->json($repo->getPublicFacets());
    }

    public function getBook(int $id, Request $request, BookRepository $repo, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        $book = $repo->find($id);
        if (!$book) return $this->json(['error' => 'Book not found'], 404);
        $payload = $security->getJwtPayload($request);
        if ($payload && isset($payload['sub'])) {
            $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
            if ($user) {
                /** @var \App\Repository\FavoriteRepository $favoriteRepo */
                $favoriteRepo = $doctrine->getRepository(Favorite::class);
                $favoriteBookIds = $favoriteRepo->getBookIdsForUser($user);
                if (in_array($book->getId(), $favoriteBookIds, true)) {
                    $book->setIsFavorite(true);
                }
            }
        }
        return $this->json($book, 200, [], ['groups' => ['book:read']]);
    }

    public function create(
        Request $request,
        ManagerRegistry $doctrine,
        SecurityService $security,
        AuthorRepository $authorRepository,
        CategoryRepository $categoryRepository
    ): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['title'])) {
            return $this->json(['error' => 'Missing title'], 400);
        }

        $authorId = $data['authorId'] ?? null;
        if (!$authorId || !ctype_digit((string) $authorId)) {
            return $this->json(['error' => 'Invalid authorId'], 400);
        }

        $author = $authorRepository->find((int) $authorId);
        if (!$author) {
            return $this->json(['error' => 'Author not found'], 404);
        }

        $categoryIds = $data['categoryIds'] ?? [];
        if (!is_array($categoryIds) || empty($categoryIds)) {
            return $this->json(['error' => 'At least one category is required'], 400);
        }

        $uniqueCategoryIds = array_unique(array_map('intval', $categoryIds));
        $categories = $categoryRepository->findBy(['id' => $uniqueCategoryIds]);
        if (count($categories) !== count($uniqueCategoryIds)) {
            return $this->json(['error' => 'One or more categories not found'], 404);
        }

        $totalCopies = isset($data['totalCopies']) ? (int) $data['totalCopies'] : (int) ($data['copies'] ?? 1);
        if ($totalCopies < 1) {
            return $this->json(['error' => 'totalCopies must be at least 1'], 400);
        }

        $desiredAvailable = isset($data['copies']) ? (int) $data['copies'] : $totalCopies;
        $desiredAvailable = max(0, min($desiredAvailable, $totalCopies));

        $book = (new Book())
            ->setTitle($data['title'])
            ->setAuthor($author)
            ->setIsbn($data['isbn'] ?? null)
            ->setDescription($data['description'] ?? null);

        foreach ($categories as $category) {
            $book->addCategory($category);
        }

        $em = $doctrine->getManager();
        $em->persist($book);
        $em->flush();

        $codePrefix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        for ($i = 1; $i <= $totalCopies; $i++) {
            $copy = (new BookCopy())
                ->setBook($book)
                ->setInventoryCode(sprintf('B%s-%03d', $codePrefix, $i))
                ->setStatus($i <= $desiredAvailable ? BookCopy::STATUS_AVAILABLE : BookCopy::STATUS_MAINTENANCE);

            $book->addInventoryCopy($copy);
            $em->persist($copy);
        }

        $book->recalculateInventoryCounters();
        $em->flush();

        return $this->json($book, 201, [], ['groups' => ['book:read']]);
    }

    public function update(
        int $id,
        Request $request,
        BookRepository $repo,
        ManagerRegistry $doctrine,
        SecurityService $security,
        AuthorRepository $authorRepository,
        CategoryRepository $categoryRepository
    ): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        $book = $repo->find($id);
        if (!$book) return $this->json(['error' => 'Book not found'], 404);
        $data = json_decode($request->getContent(), true) ?? [];

        if (!empty($data['title'])) {
            $book->setTitle($data['title']);
        }

        if (isset($data['authorId'])) {
            $authorId = $data['authorId'];
            if (!ctype_digit((string) $authorId)) {
                return $this->json(['error' => 'Invalid authorId'], 400);
            }
            $author = $authorRepository->find((int) $authorId);
            if (!$author) {
                return $this->json(['error' => 'Author not found'], 404);
            }
            $book->setAuthor($author);
        }

        if (isset($data['categoryIds']) && is_array($data['categoryIds'])) {
            $uniqueCategoryIds = array_unique(array_map('intval', $data['categoryIds']));
            if (empty($uniqueCategoryIds)) {
                return $this->json(['error' => 'At least one category is required'], 400);
            }
            $categories = $categoryRepository->findBy(['id' => $uniqueCategoryIds]);
            if (count($categories) !== count($uniqueCategoryIds)) {
                return $this->json(['error' => 'One or more categories not found'], 404);
            }
            $book->clearCategories();
            foreach ($categories as $category) {
                $book->addCategory($category);
            }
        }

        if (isset($data['totalCopies']) || isset($data['copies'])) {
            return $this->json(['error' => 'Inventory is managed automatycznie przez system wypożyczeń i nie może być edytowane ręcznie'], 400);
        }

        if (array_key_exists('description', $data)) {
            $book->setDescription($data['description']);
        }

        if (array_key_exists('isbn', $data)) {
            $book->setIsbn($data['isbn']);
        }

        $em = $doctrine->getManager();
        $em->persist($book);
        $em->flush();

        return $this->json($book, 200, [], ['groups' => ['book:read']]);
    }

    public function delete(int $id, BookRepository $repo, ManagerRegistry $doctrine, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        $book = $repo->find($id);
        if (!$book) return $this->json(['error' => 'Book not found'], 404);
        $em = $doctrine->getManager();
        $em->remove($book);
        $em->flush();
        return new JsonResponse(null, 204);
    }
}
