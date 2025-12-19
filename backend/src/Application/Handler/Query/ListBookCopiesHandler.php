<?php
namespace App\Application\Handler\Query;

use App\Application\Query\BookInventory\ListBookCopiesQuery;
use App\Entity\BookCopy;
use App\Repository\BookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
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
            throw new \RuntimeException('Book not found');
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

        return ['items' => $copies];
    }
}
