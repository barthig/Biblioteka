<?php
namespace App\Controller;

use App\Application\Command\Book\CreateBookCommand;
use App\Application\Command\Book\DeleteBookCommand;
use App\Application\Command\Book\UpdateBookCommand;
use App\Application\Query\Book\GetBookQuery;
use App\Application\Query\Book\ListBooksQuery;
use App\Controller\Traits\ValidationTrait;
use App\Request\CreateBookRequest;
use App\Request\UpdateBookRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

class BookController extends AbstractController
{
    use ValidationTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
        private readonly SecurityService $security
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

        return $this->json($result, 200, [], ['groups' => ['book:read']]);
    }

    public function filters(): JsonResponse
    {
        // TODO: Implementacja GetBookFacetsQuery
        return $this->json(['error' => 'Not implemented'], 501);
    }

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
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
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
        } catch (\RuntimeException $e) {
            $statusCode = match (true) {
                str_contains($e->getMessage(), 'Author not found') => 404,
                str_contains($e->getMessage(), 'categories not found') => 404,
                str_contains($e->getMessage(), 'At least one category') => 400,
                default => 500
            };
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function update(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        
        if (isset($data['copies']) || isset($data['totalCopies'])) {
            return $this->json(['error' => 'Inventory is managed automatycznie przez system wypożyczeń i nie może być edytowane ręcznie'], 400);
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
        } catch (\RuntimeException $e) {
            $statusCode = match (true) {
                str_contains($e->getMessage(), 'Book not found') => 404,
                str_contains($e->getMessage(), 'Author not found') => 404,
                str_contains($e->getMessage(), 'categories not found') => 404,
                str_contains($e->getMessage(), 'At least one category') => 400,
                default => 500
            };
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function delete(int $id, Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $command = new DeleteBookCommand(bookId: $id);

        try {
            $this->commandBus->dispatch($command);
            return new JsonResponse(null, 204);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    public function recommended(Request $request): JsonResponse
    {
        // TODO: Implementacja GetRecommendedBooksQuery
        return $this->json(['error' => 'Not implemented'], 501);
    }
}
