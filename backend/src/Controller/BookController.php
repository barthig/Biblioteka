<?php
namespace App\Controller;

use App\Controller\Traits\ValidationTrait;
use App\Entity\Book;
use App\Entity\Favorite;
use App\Entity\User;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Request\CreateBookRequest;
use App\Request\UpdateBookRequest;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\BookCopy;
use OpenApi\Attributes as OA;

class BookController extends AbstractController
{
    use ValidationTrait;
    
    #[OA\Get(
        path: '/api/books',
        summary: 'Lista książek z filtrowaniem i paginacją',
        description: 'Zwraca listę książek dostępnych w systemie z możliwością filtrowania.',
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 10, maximum: 100)),
            new OA\Parameter(name: 'q', in: 'query', description: 'Wyszukiwanie pełnotekstowe', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'authorId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'categoryId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'publisher', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'resourceType', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'yearFrom', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'yearTo', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ageGroup', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'available', in: 'query', schema: new OA\Schema(type: 'boolean'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista książek',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Book')
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'page', type: 'integer'),
                                new OA\Property(property: 'limit', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                                new OA\Property(property: 'totalPages', type: 'integer')
                            ],
                            type: 'object'
                        )
                    ]
                )
            )
        ]
    )]
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
            'ageGroup' => $request->query->get('ageGroup'),
            'page' => $request->query->getInt('page', 1),
            'limit' => $request->query->getInt('limit', 20),
        ];

        if ($request->query->has('available')) {
            $filters['available'] = $request->query->get('available');
        }

        $result = $repo->searchPublic($filters);
        $books = $result['data'];

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

        return $this->json([
            'data' => $books,
            'meta' => $result['meta']
        ], 200, [], ['groups' => ['book:read']]);
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
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator
    ): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $dto = $this->mapArrayToDto($data, new CreateBookRequest());
        
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $author = $authorRepository->find($dto->authorId);
        if (!$author) {
            return $this->json(['error' => 'Author not found'], 404);
        }

        $categories = [];
        if (!empty($dto->categoryIds)) {
            $uniqueCategoryIds = array_unique(array_map('intval', $dto->categoryIds));
            $categories = $categoryRepository->findBy(['id' => $uniqueCategoryIds]);
            if (count($categories) !== count($uniqueCategoryIds)) {
                return $this->json(['error' => 'One or more categories not found'], 404);
            }
        }

        if (empty($categories)) {
            return $this->json(['error' => 'At least one category is required'], 400);
        }

        // Obsługa copies i totalCopies - dla kompatybilności wstecznej
        $totalCopies = $dto->totalCopies ?? ($data['copies'] ?? 1);
        $desiredAvailable = isset($data['copies']) ? (int)$data['copies'] : $totalCopies;
        $desiredAvailable = max(0, min($desiredAvailable, $totalCopies));

        $book = (new Book())
            ->setTitle($dto->title)
            ->setAuthor($author)
            ->setIsbn($dto->isbn)
            ->setDescription($dto->description);

        if ($dto->publisher) {
            $book->setPublisher($dto->publisher);
        }
        if ($dto->publicationYear) {
            $book->setPublicationYear($dto->publicationYear);
        }
        if ($dto->resourceType) {
            $book->setResourceType($dto->resourceType);
        }
        if ($dto->signature) {
            $book->setSignature($dto->signature);
        }
        if ($dto->targetAgeGroup) {
            $book->setTargetAgeGroup($dto->targetAgeGroup);
        }

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
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator
    ): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $book = $repo->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        
        // Sprawdź czy próbuje się edytować inwentarz
        if (isset($data['copies']) || isset($data['totalCopies'])) {
            return $this->json(['error' => 'Inventory is managed automatycznie przez system wypożyczeń i nie może być edytowane ręcznie'], 400);
        }
        
        $dto = $this->mapArrayToDto($data, new UpdateBookRequest());
        
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        if ($dto->title !== null) {
            $book->setTitle($dto->title);
        }

        if ($dto->authorId !== null) {
            $author = $authorRepository->find($dto->authorId);
            if (!$author) {
                return $this->json(['error' => 'Author not found'], 404);
            }
            $book->setAuthor($author);
        }

        if ($dto->categoryIds !== null) {
            if (empty($dto->categoryIds)) {
                return $this->json(['error' => 'At least one category is required'], 400);
            }
            $uniqueCategoryIds = array_unique(array_map('intval', $dto->categoryIds));
            $categories = $categoryRepository->findBy(['id' => $uniqueCategoryIds]);
            if (count($categories) !== count($uniqueCategoryIds)) {
                return $this->json(['error' => 'One or more categories not found'], 404);
            }
            $book->clearCategories();
            foreach ($categories as $category) {
                $book->addCategory($category);
            }
        }

        if ($dto->description !== null) {
            $book->setDescription($dto->description);
        }

        if ($dto->isbn !== null) {
            $book->setIsbn($dto->isbn);
        }

        if ($dto->publisher !== null) {
            $book->setPublisher($dto->publisher);
        }

        if ($dto->publicationYear !== null) {
            $book->setPublicationYear($dto->publicationYear);
        }

        if ($dto->resourceType !== null) {
            $book->setResourceType($dto->resourceType);
        }

        if ($dto->signature !== null) {
            $book->setSignature($dto->signature);
        }

        if ($dto->targetAgeGroup !== null) {
            $book->setTargetAgeGroup($dto->targetAgeGroup);
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

    public function recommended(Request $request, BookRepository $repo, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        $limit = $request->query->getInt('limit', 6);
        if ($limit < 1) {
            $limit = 1;
        } elseif ($limit > 24) {
            $limit = 24;
        }

        $payload = $security->getJwtPayload($request);
        $favoriteLookup = [];
        if ($payload && isset($payload['sub'])) {
            $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
            if ($user) {
                /** @var \App\Repository\FavoriteRepository $favoriteRepo */
                $favoriteRepo = $doctrine->getRepository(Favorite::class);
                $favoriteBookIds = $favoriteRepo->getBookIdsForUser($user);
                if (!empty($favoriteBookIds)) {
                    $favoriteLookup = array_flip($favoriteBookIds);
                }
            }
        }

        $groupsPayload = [];
        foreach (Book::getAgeGroupDefinitions() as $key => $definition) {
            $books = $repo->findRecommendedByAgeGroup($key, $limit);
            if (!empty($favoriteLookup)) {
                foreach ($books as $book) {
                    if ($book instanceof Book && $book->getId() !== null && isset($favoriteLookup[$book->getId()])) {
                        $book->setIsFavorite(true);
                    }
                }
            }

            $groupsPayload[] = [
                'key' => $key,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'books' => $books,
            ];
        }

        return $this->json([
            'groups' => $groupsPayload,
        ], 200, [], ['groups' => ['book:read']]);
    }
}
