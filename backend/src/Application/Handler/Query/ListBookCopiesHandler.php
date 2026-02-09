<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\BookInventory\ListBookCopiesQuery;
use App\Entity\BookCopy;
use App\Exception\NotFoundException;
use App\Repository\BookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class ListBookCopiesHandler
{
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {
    }

    public function __invoke(ListBookCopiesQuery $query): array
    {
        $book = $this->bookRepository->find($query->bookId);
        if (!$book) {
            throw NotFoundException::forBook($query->bookId);
        }

        $copies = [];
        foreach ($book->getInventory() as $copy) {
            if (!$copy instanceof BookCopy) {
                continue;
            }
            $copies[] = [
                'id' => $copy->getId(),
                'inventoryCode' => $copy->getInventoryCode(),
                'status' => $copy->getStatus(),
                'accessType' => $copy->getAccessType(),
                'location' => $copy->getLocation(),
                'conditionState' => $copy->getConditionState(),
                'createdAt' => $copy->getCreatedAt()->format(DATE_ATOM),
                'updatedAt' => $copy->getUpdatedAt()->format(DATE_ATOM),
            ];
        }

        return ['data' => $copies];
    }
}
