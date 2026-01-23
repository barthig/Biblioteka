<?php
namespace App\Controller;

use App\Application\Command\Author\CreateAuthorCommand;
use App\Application\Command\Author\DeleteAuthorCommand;
use App\Application\Command\Author\UpdateAuthorCommand;
use App\Application\Query\Author\GetAuthorQuery;
use App\Application\Query\Author\ListAuthorsQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

class AuthorController extends AbstractController
{
    #[OA\Tag(name: 'Author')]
    public function __construct(
        private readonly MessageBusInterface $bus
    ) {
    }

    #[OA\Get(
        path: '/api/authors',
        summary: 'Lista autorów',
        description: 'Zwraca listę autorów z opcjonalnym wyszukiwaniem i paginacją',
        tags: ['Authors'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista autorów', content: new OA\JsonContent(type: 'object'))
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $search = $request->query->get('search');

        $query = new ListAuthorsQuery(
            page: $page,
            limit: $limit,
            search: $search
        );

        $envelope = $this->bus->dispatch($query);
        $authors = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json($authors, Response::HTTP_OK, [], ['groups' => ['book:read']]);
    }

    #[OA\Get(
        path: '/api/authors/{id}',
        summary: 'Szczegóły autora',
        description: 'Zwraca szczegółowe informacje o autorze wraz z jego książkami',
        tags: ['Authors'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Szczegóły autora', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 404, description: 'Autor nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function get(int $id): JsonResponse
    {
        try {
            $query = new GetAuthorQuery(authorId: $id);
            $envelope = $this->bus->dispatch($query);
            $author = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json($author, Response::HTTP_OK, [], ['groups' => ['book:read']]);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    #[OA\Post(
        path: '/api/authors',
        summary: 'Utwórz autora',
        description: 'Tworzy nowego autora. Wymaga roli LIBRARIAN.',
        tags: ['Authors'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Autor utworzony', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Błąd walidacji', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['message' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        $command = new CreateAuthorCommand(name: $data['name']);
        $envelope = $this->bus->dispatch($command);
        $author = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($author, Response::HTTP_CREATED, [], ['groups' => ['book:read']]);
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    #[OA\Put(
        path: '/api/authors/{id}',
        summary: 'Aktualizuj autora',
        description: 'Aktualizuje dane autora. Wymaga roli LIBRARIAN.',
        tags: ['Authors'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Autor zaktualizowany', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Autor nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $command = new UpdateAuthorCommand(
                authorId: $id,
                name: $data['name'] ?? null
            );

            $envelope = $this->bus->dispatch($command);
            $author = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json($author, Response::HTTP_OK, [], ['groups' => ['book:read']]);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    #[OA\Delete(
        path: '/api/authors/{id}',
        summary: 'Usuń autora',
        description: 'Usuwa autora. Wymaga roli LIBRARIAN.',
        tags: ['Authors'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Autor usunięty'),
            new OA\Response(response: 403, description: 'Brak uprawnień', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Autor nie znaleziony', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        try {
            $command = new DeleteAuthorCommand(authorId: $id);
            $this->bus->dispatch($command);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}

