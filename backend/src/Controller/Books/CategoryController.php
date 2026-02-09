<?php
declare(strict_types=1);
namespace App\Controller\Books;

use App\Application\Command\Category\CreateCategoryCommand;
use App\Application\Command\Category\DeleteCategoryCommand;
use App\Application\Command\Category\UpdateCategoryCommand;
use App\Application\Query\Category\GetCategoryQuery;
use App\Application\Query\Category\ListCategoriesQuery;
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

#[OA\Tag(name: 'Category')]
class CategoryController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly MessageBusInterface $bus
    ) {
    }

    #[OA\Get(
        path: '/api/categories',
        summary: 'List categories',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Category')
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 50)));

        $query = new ListCategoriesQuery(page: $page, limit: $limit);
        $envelope = $this->bus->dispatch($query);
        $categories = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json($categories, Response::HTTP_OK, [], ['groups' => ['book:read']]);
    }

    #[OA\Get(
        path: '/api/categories/{id}',
        summary: 'Get category by id',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function get(int $id): JsonResponse
    {
        try {
            $query = new GetCategoryQuery(categoryId: $id);
            $envelope = $this->bus->dispatch($query);
            $category = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json($category, Response::HTTP_OK, [], ['groups' => ['book:read']]);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->jsonErrorMessage(Response::HTTP_NOT_FOUND, $e->getMessage());
        }
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    #[OA\Post(
        path: '/api/categories',
        summary: 'Create category',
        tags: ['Categories'],
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
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->jsonErrorMessage(400, 'Name is required');
        }

        $command = new CreateCategoryCommand(name: $data['name']);
        $envelope = $this->bus->dispatch($command);
        $category = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($category, Response::HTTP_CREATED, [], ['groups' => ['book:read']]);
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    #[OA\Put(
        path: '/api/categories/{id}',
        summary: 'Update category',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'object')
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
            new OA\Response(response: 400, description: 'Invalid id or payload', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $command = new UpdateCategoryCommand(
                categoryId: $id,
                name: $data['name'] ?? null
            );

            $envelope = $this->bus->dispatch($command);
            $category = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json($category, Response::HTTP_OK, [], ['groups' => ['book:read']]);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->jsonErrorMessage(Response::HTTP_NOT_FOUND, $e->getMessage());
        }
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    #[OA\Delete(
        path: '/api/categories/{id}',
        summary: 'Delete category',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 400, description: 'Invalid id', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        try {
            $command = new DeleteCategoryCommand(categoryId: $id);
            $this->bus->dispatch($command);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->jsonErrorMessage(Response::HTTP_NOT_FOUND, $e->getMessage());
        }
    }
}

