<?php
declare(strict_types=1);

namespace App\Application\QueryHandler\Recommendation;

use App\Application\Query\Recommendation\FindSimilarBooksQuery;
use App\Repository\BookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
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
