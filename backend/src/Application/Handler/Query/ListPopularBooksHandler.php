<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Book\ListPopularBooksQuery;
use App\Repository\BookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class ListPopularBooksHandler
{
    public function __construct(
        private BookRepository $bookRepository
    ) {
    }

    public function __invoke(ListPopularBooksQuery $query): array
    {
        return $this->bookRepository->findPopularBooks($query->limit);
    }
}
