<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Book\ListNewestBooksQuery;
use App\Repository\BookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListNewestBooksHandler
{
    public function __construct(
        private BookRepository $bookRepository
    ) {
    }

    public function __invoke(ListNewestBooksQuery $query): array
    {
        return $this->bookRepository->findNewestBooks($query->limit);
    }
}
