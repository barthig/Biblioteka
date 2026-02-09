<?php
declare(strict_types=1);

namespace App\Application\Handler\Query;

use App\Application\Query\Recommendation\FindSimilarBooksQuery;
use App\Repository\BookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class FindSimilarBooksQueryHandler
{
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {
    }

    public function __invoke(FindSimilarBooksQuery $query): array
    {
        return $this->bookRepository->findSimilarBooks($query->vector, $query->limit);
    }
}
