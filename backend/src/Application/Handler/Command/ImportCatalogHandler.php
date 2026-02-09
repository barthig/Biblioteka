<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Catalog\ImportCatalogCommand;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Repository\AuthorRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ImportCatalogHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthorRepository $authorRepository,
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    /**
     * @return array{importedCount: int, items: string[]}
     */
    public function __invoke(ImportCatalogCommand $command): array
    {
        $imported = [];
        
        foreach ($command->items as $item) {
            $title = isset($item['title']) && is_string($item['title']) ? trim($item['title']) : null;
            $authorName = isset($item['author']) ? $item['author'] : null;
            if (is_array($authorName) && isset($authorName['name'])) {
                $authorName = $authorName['name'];
            }
            $authorName = is_string($authorName) ? trim($authorName) : null;

            if ($title === null || $title === '' || $authorName === null || $authorName === '') {
                continue;
            }

            $author = $this->authorRepository->findOneBy(['name' => $authorName]);
            if (!$author) {
                continue;
            }

            $book = new Book();
            $book
                ->setTitle($title)
                ->setAuthor($author)
                ->setIsbn(isset($item['isbn']) ? ($item['isbn'] ?: null) : null)
                ->setPublisher(isset($item['publisher']) ? ($item['publisher'] ?: null) : null)
                ->setPublicationYear(isset($item['publicationYear']) ? (int) $item['publicationYear'] : null)
                ->setResourceType(isset($item['resourceType']) ? ($item['resourceType'] ?: null) : null)
                ->setSignature(isset($item['signature']) ? ($item['signature'] ?: null) : null)
                ->setDescription(isset($item['description']) ? ($item['description'] ?: null) : null);

            if (isset($item['categoryIds']) && is_array($item['categoryIds'])) {
                $categories = $this->categoryRepository->findBy(['id' => $item['categoryIds']]);
                foreach ($categories as $category) {
                    $book->addCategory($category);
                }
            }

            $this->entityManager->persist($book);

            $count = isset($item['copies']) ? max(0, (int) $item['copies']) : 0;
            for ($i = 0; $i < $count; ++$i) {
                $copy = (new BookCopy())
                    ->setInventoryCode(sprintf('IMPORT-%s-%03d', strtoupper(bin2hex(random_bytes(3))), $i + 1))
                    ->setStatus(BookCopy::STATUS_AVAILABLE)
                    ->setAccessType(BookCopy::ACCESS_STORAGE);

                $book->addInventoryCopy($copy);
                $this->entityManager->persist($copy);
            }

            $book->recalculateInventoryCounters();
            $imported[] = $title;
        }

        $this->entityManager->flush();

        return [
            'importedCount' => count($imported),
            'items' => $imported,
        ];
    }
}
