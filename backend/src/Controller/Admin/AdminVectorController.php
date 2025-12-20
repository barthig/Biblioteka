<?php
namespace App\Controller\Admin;

use App\Message\UpdateBookEmbeddingMessage;
use App\Repository\BookRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class AdminVectorController extends AbstractController
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly MessageBusInterface $bus,
        private readonly SecurityService $security
    ) {
    }

    public function reindexAll(Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
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

    public function stats(Request $request): JsonResponse
    {
        if (!$this->security->hasRole($request, 'ROLE_ADMIN')) {
            return $this->json(['message' => 'Forbidden'], 403);
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
