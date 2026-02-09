<?php
declare(strict_types=1);

namespace App\Application\QueryHandler\Book;

use App\Application\Query\Book\ExportBooksQuery;
use App\Repository\BookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ExportBooksQueryHandler
{
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {
    }

    public function __invoke(ExportBooksQuery $query): array
    {
        return $this->bookRepository->findAll();
    }
}
