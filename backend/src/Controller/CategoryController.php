<?php
namespace App\Controller;

use App\Application\Command\Category\CreateCategoryCommand;
use App\Application\Command\Category\DeleteCategoryCommand;
use App\Application\Command\Category\UpdateCategoryCommand;
use App\Application\Query\Category\GetCategoryQuery;
use App\Application\Query\Category\ListCategoriesQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CategoryController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $bus
    ) {
    }

    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 50)));

        $query = new ListCategoriesQuery(page: $page, limit: $limit);
        $envelope = $this->bus->dispatch($query);
        $categories = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json($categories, Response::HTTP_OK, [], ['groups' => ['book:read']]);
    }

    public function get(int $id): JsonResponse
    {
        try {
            $query = new GetCategoryQuery(categoryId: $id);
            $envelope = $this->bus->dispatch($query);
            $category = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json($category, Response::HTTP_OK, [], ['groups' => ['book:read']]);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        $command = new CreateCategoryCommand(name: $data['name']);
        $envelope = $this->bus->dispatch($command);
        $category = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($category, Response::HTTP_CREATED, [], ['groups' => ['book:read']]);
    }

    #[IsGranted('ROLE_LIBRARIAN')]
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
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    public function delete(int $id): JsonResponse
    {
        try {
            $command = new DeleteCategoryCommand(categoryId: $id);
            $this->bus->dispatch($command);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
