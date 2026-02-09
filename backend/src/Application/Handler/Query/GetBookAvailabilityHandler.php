<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Book\GetBookAvailabilityQuery;
use App\Repository\BookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetBookAvailabilityHandler
{
    public function __construct(
        private BookRepository $bookRepository
    ) {
    }

    public function __invoke(GetBookAvailabilityQuery $query): ?array
    {
        return $this->bookRepository->getBookAvailability($query->bookId);
    }
}
