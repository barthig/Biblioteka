<?php
namespace App\Controller;

use App\Application\Command\Book\CreateBookCommand;
use App\Application\Command\Book\DeleteBookCommand;
use App\Application\Command\Book\UpdateBookCommand;
use App\Application\Query\Book\GetBookQuery;
use App\Application\Query\Book\ListBooksQuery;
use App\Application\Query\User\GetUserByIdQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Controller\Traits\ValidationTrait;
use App\Entity\Book;
use App\Request\CreateBookRequest;
use App\Request\UpdateBookRequest;
use App\Dto\ApiError;
use App\Service\PersonalizedRecommendationService;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Book')]
class BookController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
        private readonly SecurityService $security,
        private readonly PersonalizedRecommendationService $recommendations
    ) {}

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
    public function list(Request $request): JsonResponse
    {
        $query = new ListBooksQuery(
            q: $request->query->get('q'),
            authorId: $request->query->has('authorId') ? $request->query->getInt('authorId') : null,
            categoryId: $request->query->has('categoryId') ? $request->query->getInt('categoryId') : null,
            publisher: $request->query->get('publisher'),
            resourceType: $request->query->get('resourceType'),
            signature: $request->query->get('signature'),
            yearFrom: $request->query->has('yearFrom') ? $request->query->getInt('yearFrom') : null,
            yearTo: $request->query->has('yearTo') ? $request->query->getInt('yearTo') : null,
            ageGroup: $request->query->get('ageGroup'),
            available: $request->query->has('available') ? $request->query->get('available') : null,
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 20),
            userId: $this->security->getCurrentUserId($request)
        );

        $envelope = $this->queryBus->dispatch($query);
        $result = $envelope->last(HandledStamp::class)?->getResult();

        // Add HATEOAS links to books
        if (isset($result['items']) && is_array($result['items'])) {
            foreach ($result['items'] as &$book) {
                if (isset($book['id'])) {
                    $book['_links'] = [
                        'self' => ['href' => '/api/books/' . $book['id']],
                        'copies' => ['href' => '/api/books/' . $book['id'] . '/copies'],
                        'loans' => ['href' => '/api/loans?bookId=' . $book['id']],
                        'ratings' => ['href' => '/api/books/' . $book['id'] . '/ratings']
                    ];
                }
            }
        }

        // Add pagination links
        if (isset($result['meta'])) {
            $page = $result['meta']['page'] ?? 1;
            $totalPages = $result['meta']['totalPages'] ?? 1;
            
            $result['_links'] = [
                'self' => ['href' => '/api/books?page=' . $page],
            ];
            
            if ($page > 1) {
                $result['_links']['prev'] = ['href' => '/api/books?page=' . ($page - 1)];
                $result['_links']['first'] = ['href' => '/api/books?page=1'];
            }
            
            if ($page < $totalPages) {
                $result['_links']['next'] = ['href' => '/api/books?page=' . ($page + 1)];
                $result['_links']['last'] = ['href' => '/api/books?page=' . $totalPages];
            }
        }

        return $this->json($result, 200, [], ['groups' => ['book:read']]);
    }

    #[OA\Get(
        path: '/api/books/filters',
        summary: 'Dostępne filtry dla książek',
        description: 'Zwraca listę dostępnych wartości filtrów (grupy wiekowe)',
        tags: ['Books'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista filtrów',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ageGroups', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            )
        ]
    )]
    public function filters(): JsonResponse
    {
        $ageGroups = [];
        $ageGroupDefinitions = [
            Book::AGE_GROUP_TODDLERS => '0-2 lata',
            Book::AGE_GROUP_PRESCHOOL => '3-6 lat',
            Book::AGE_GROUP_EARLY_SCHOOL => '7-9 lat',
            Book::AGE_GROUP_MIDDLE_GRADE => '10-12 lat',
            Book::AGE_GROUP_YA_EARLY => '13-15 lat',
            Book::AGE_GROUP_YA_LATE => '16+ lat',
        ];

        foreach ($ageGroupDefinitions as $value => $label) {
            $ageGroups[] = ['value' => $value, 'label' => $label];
        }

        return $this->json(['ageGroups' => $ageGroups]);
    }

    #[OA\Get(
        path: '/api/books/{id}',
        summary: 'Szczegóły książki',
        description: 'Zwraca pełne informacje o książce, włącznie z relacjami i dostępnością',
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Szczegóły książki', content: new OA\JsonContent(ref: '#/components/schemas/Book')),
            new OA\Response(response: 404, description: 'Książka nie znaleziona', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function getBook(int $id, Request $request): JsonResponse
    {
        $query = new GetBookQuery(
            bookId: $id,
            userId: $this->security->getCurrentUserId($request)
        );

        try {
            $envelope = $this->queryBus->dispatch($query);
            $book = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($book, 200, [], ['groups' => ['book:read']]);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonError(ApiError::notFound($e->getMessage()));
        }
    }

    #[OA\Post(
        path: '/api/books',
        summary: 'Utwórz nową książkę',
        description: 'Tworzy nową książkę w katalogu. Wymaga roli LIBRARIAN.',
        tags: ['Books'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'authorId', 'categoryIds'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'authorId', type: 'integer'),
                    new OA\Property(property: 'categoryIds', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'isbn', type: 'string'),
                    new OA\Property(property: 'publisher', type: 'string'),
                    new OA\Property(property: 'publicationYear', type: 'integer'),
                    new OA\Property(property: 'resourceType', type: 'string'),
                    new OA\Property(property: 'signature', type: 'string'),
                    new OA\Property(property: 'targetAgeGroup', type: 'string'),
                    new OA\Property(property: 'totalCopies', type: 'integer'),
                    new OA\Property(property: 'copies', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Książka utworzona', content: new OA\JsonContent(ref: '#/components/schemas/Book')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Autor lub kategoria nie znaleziona', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $dto = $this->mapArrayToDto($data, new CreateBookRequest());
        
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $totalCopies = $dto->totalCopies ?? ($data['copies'] ?? 1);
        $desiredAvailable = isset($data['copies']) ? (int)$data['copies'] : $totalCopies;
        $desiredAvailable = max(0, min($desiredAvailable, $totalCopies));

        $command = new CreateBookCommand(
            title: $dto->title,
            authorId: $dto->authorId,
            categoryIds: $dto->categoryIds ?? [],
            description: $dto->description,
            isbn: $dto->isbn,
            publisher: $dto->publisher,
            publicationYear: $dto->publicationYear,
            resourceType: $dto->resourceType,
            signature: $dto->signature,
            targetAgeGroup: $dto->targetAgeGroup,
            totalCopies: $totalCopies,
            availableCopies: $desiredAvailable
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $book = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($book, 201, [], ['groups' => ['book:read']]);
        } catch (\Throwable $e) {
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? $e;
            }
            if ($e instanceof HttpExceptionInterface) {
                return $this->jsonError(ApiError::fromException($e));
            }
            $statusCode = match (true) {
                str_contains($e->getMessage(), 'Author not found') => 404,
                str_contains($e->getMessage(), 'categories not found') => 404,
                str_contains($e->getMessage(), 'At least one category') => 400,
                default => 500
            };
            if ($statusCode === 404) {
                return $this->jsonError(ApiError::notFound($e->getMessage()));
            }
            return $this->jsonError(ApiError::badRequest($e->getMessage()));
        }
    }

    #[OA\Put(
        path: '/api/books/{id}',
        summary: 'Aktualizuj książkę',
        description: 'Aktualizuje dane książki. Wymaga roli LIBRARIAN. Nie można edytować liczby kopii.',
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'authorId', type: 'integer'),
                    new OA\Property(property: 'categoryIds', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'isbn', type: 'string'),
                    new OA\Property(property: 'publisher', type: 'string'),
                    new OA\Property(property: 'publicationYear', type: 'integer'),
                    new OA\Property(property: 'resourceType', type: 'string'),
                    new OA\Property(property: 'signature', type: 'string'),
                    new OA\Property(property: 'targetAgeGroup', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Książka zaktualizowana', content: new OA\JsonContent(ref: '#/components/schemas/Book')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Książka, autor lub kategoria nie znaleziona', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function update(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $data = json_decode($request->getContent(), true) ?? [];
        
        if (isset($data['copies']) || isset($data['totalCopies'])) {
            return $this->jsonError(ApiError::badRequest('Inventory is managed automatycznie przez system wypożyczeń i nie może być edytowane ręcznie'));
        }
        
        $dto = $this->mapArrayToDto($data, new UpdateBookRequest());
        
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $command = new UpdateBookCommand(
            bookId: $id,
            title: $dto->title,
            authorId: $dto->authorId,
            categoryIds: $dto->categoryIds,
            description: $dto->description,
            isbn: $dto->isbn,
            publisher: $dto->publisher,
            publicationYear: $dto->publicationYear,
            resourceType: $dto->resourceType,
            signature: $dto->signature,
            targetAgeGroup: $dto->targetAgeGroup
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $book = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($book, 200, [], ['groups' => ['book:read']]);
        } catch (\Throwable $e) {
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? $e;
            }
            if ($e instanceof HttpExceptionInterface) {
                return $this->jsonError(ApiError::fromException($e));
            }
            $statusCode = match (true) {
                str_contains($e->getMessage(), 'Book not found') => 404,
                str_contains($e->getMessage(), 'Author not found') => 404,
                str_contains($e->getMessage(), 'categories not found') => 404,
                str_contains($e->getMessage(), 'At least one category') => 400,
                default => 500
            };
            if ($statusCode === 404) {
                return $this->jsonError(ApiError::notFound($e->getMessage()));
            }
            return $this->jsonError(ApiError::badRequest($e->getMessage()));
        }
    }

    #[OA\Delete(
        path: '/api/books/{id}',
        summary: 'Usuń książkę',
        description: 'Usuwa książkę z katalogu. Wymaga roli LIBRARIAN.',
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Książka usunięta'),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Książka nie znaleziona', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function delete(int $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $command = new DeleteBookCommand(bookId: $id);

        try {
            $this->commandBus->dispatch($command);
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonError(ApiError::notFound($e->getMessage()));
        }
    }

    #[OA\Get(
        path: '/api/books/recommended',
        summary: 'Rekomendacje książek',
        description: 'Zwraca spersonalizowane rekomendacje książek dla zalogowanego użytkownika lub ogólne dla gości',
        tags: ['Books'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Grupy rekomendowanych książek',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'groups', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Błąd serwera', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function recommended(Request $request): JsonResponse
    {
        try {
            $start = microtime(true);
            error_log('BookController::recommended - START');
            $userId = $this->security->getCurrentUserId($request);
            error_log('BookController::recommended - userId: ' . ($userId ?? 'null'));
            
            $user = null;
            if ($userId) {
                $envelope = $this->queryBus->dispatch(new GetUserByIdQuery($userId));
                $user = $envelope->last(HandledStamp::class)?->getResult();
            }
            error_log('BookController::recommended - user loaded: ' . ($user ? 'yes' : 'no'));

            error_log('BookController::recommended - calling getRecommendationsForUser');
            $recoStart = microtime(true);
            $groups = $this->recommendations->getRecommendationsForUser($user);
            $recoMs = (int) round((microtime(true) - $recoStart) * 1000);
            error_log('BookController::recommended - got ' . count($groups) . ' groups');
            $totalMs = (int) round((microtime(true) - $start) * 1000);
            error_log('BookController::recommended - reco in ' . $recoMs . 'ms, total ' . $totalMs . 'ms');

            return $this->json(['groups' => $groups], 200, [], ['groups' => ['book:read']]);
        } catch (\Exception $e) {
            error_log('BookController::recommended - EXCEPTION: ' . $e->getMessage());
            error_log('BookController::recommended - Exception type: ' . get_class($e));
            error_log('BookController::recommended - File: ' . $e->getFile() . ':' . $e->getLine());
            error_log('BookController::recommended - Stack trace: ' . $e->getTraceAsString());
            return $this->jsonError(ApiError::internalError($e->getMessage()));
        }
    }

    #[OA\Get(
        path: '/api/books/popular',
        summary: 'Popularne książki',
        description: 'Zwraca listę najpopularniejszych książek',
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 50))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista popularnych książek',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Book'))
                    ]
                )
            )
        ]
    )]
    public function popular(Request $request): JsonResponse
    {
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        
        $books = $this->queryBus->dispatch(
            new \App\Application\Query\Book\ListPopularBooksQuery($limit)
        )->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json(['data' => $books], 200, [], ['groups' => ['book:read']]);
    }

    #[OA\Get(
        path: '/api/books/newest',
        summary: 'Najnowsze książki',
        description: 'Zwraca listę ostatnio dodanych książek',
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 50))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista najnowszych książek',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Book'))
                    ]
                )
            )
        ]
    )]
    public function newest(Request $request): JsonResponse
    {
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        
        $books = $this->queryBus->dispatch(
            new \App\Application\Query\Book\ListNewestBooksQuery($limit)
        )->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json(['data' => $books], 200, [], ['groups' => ['book:read']]);
    }

    #[OA\Get(
        path: '/api/books/{id}/availability',
        summary: 'Dostępność książki',
        description: 'Zwraca informacje o dostępności egzemplarzy książki',
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Informacje o dostępności', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 404, description: 'Książka nie znaleziona', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function availability(int $id): JsonResponse
    {
        $availability = $this->queryBus->dispatch(
            new \App\Application\Query\Book\GetBookAvailabilityQuery($id)
        )->last(HandledStamp::class)?->getResult();

        if (!$availability) {
            return $this->jsonError(ApiError::notFound('Book not found'));
        }

        return $this->json($availability, 200);
    }
}
