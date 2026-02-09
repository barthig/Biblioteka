<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Catalog\ExportCatalogQuery;
use App\Entity\Book;
use App\Repository\BookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class ExportCatalogHandler
{
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {
    }

    public function __invoke(ExportCatalogQuery $query): array
    {
        $books = $this->bookRepository->findBy([], ['id' => 'ASC']);
        $payload = array_map(function (Book $book) {
            $categories = [];
            foreach ($book->getCategories() as $category) {
                $categories[] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                ];
            }

            return [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'author' => [
                    'id' => $book->getAuthor()->getId(),
                    'name' => $book->getAuthor()->getName(),
                ],
                'isbn' => $book->getIsbn(),
                'publisher' => $book->getPublisher(),
                'publicationYear' => $book->getPublicationYear(),
                'resourceType' => $book->getResourceType(),
                'signature' => $book->getSignature(),
                'description' => $book->getDescription(),
                'categories' => $categories,
                'copies' => $book->getCopies(),
                'totalCopies' => $book->getTotalCopies(),
            ];
        }, $books);

        return ['items' => $payload];
    }
}
