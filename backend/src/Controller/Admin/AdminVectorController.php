<?php
namespace App\Controller\Admin;

use App\Message\UpdateBookEmbeddingMessage;
use App\Repository\BookRepository;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin/Vector')]
class AdminVectorController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly BookRepository $books,
        private readonly MessageBusInterface $bus,
        private readonly SecurityService $security
    ) {
    }

    #[OA\Post(
        path: '/api/admin/books/embeddings/reindex',
        summary: 'Reindex all book embeddings',
        tags: ['Admin/Vector'],
        responses: [
            new OA\Response(response: 202, description: 'Accepted', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function reindexAll(Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $qb = $this->books->createQueryBuilder('b')->select('b.id');
        $iterable = $qb->getQuery()->toIterable();

        $count = 0;
        foreach ($iterable as $row) {
            $id = is_array($row) ? (int) ($row['id'] ?? 0) : 0;
            if ($id <= 0) {
                continue;
            }
            $this->bus->dispatch(new UpdateBookEmbeddingMessage($id));
            ++$count;
        }

        return $this->json(['dispatched' => $count], 202);
    }

    #[OA\Get(
        path: '/api/admin/books/embeddings/stats',
        summary: 'Get embedding statistics',
        tags: ['Admin/Vector'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function stats(Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->jsonErrorMessage(403, 'Forbidden');
        }

        $total = (int) $this->books->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $withEmbedding = (int) $this->books->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.embedding IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->json([
            'totalBooks' => $total,
            'embeddedBooks' => $withEmbedding,
            'missingEmbeddings' => max(0, $total - $withEmbedding),
        ]);
    }
}

