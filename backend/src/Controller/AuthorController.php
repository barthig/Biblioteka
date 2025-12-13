<?php
namespace App\Controller;

use App\Application\Command\Author\CreateAuthorCommand;
use App\Application\Command\Author\DeleteAuthorCommand;
use App\Application\Command\Author\UpdateAuthorCommand;
use App\Application\Query\Author\GetAuthorQuery;
use App\Application\Query\Author\ListAuthorsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AuthorController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $bus
    ) {
    }

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

    public function get(int $id): JsonResponse
    {
        try {
            $query = new GetAuthorQuery(authorId: $id);
            $envelope = $this->bus->dispatch($query);
            $author = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json($author, Response::HTTP_OK, [], ['groups' => ['book:read']]);
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

        $command = new CreateAuthorCommand(name: $data['name']);
        $envelope = $this->bus->dispatch($command);
        $author = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($author, Response::HTTP_CREATED, [], ['groups' => ['book:read']]);
    }

    #[IsGranted('ROLE_LIBRARIAN')]
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
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('ROLE_LIBRARIAN')]
    public function delete(int $id): JsonResponse
    {
        try {
            $command = new DeleteAuthorCommand(authorId: $id);
            $this->bus->dispatch($command);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
