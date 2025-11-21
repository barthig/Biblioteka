<?php
namespace App\Service\Maintenance;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class IsbnImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookRepository $books,
        private AuthorRepository $authors,
        private CategoryRepository $categories
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @return array{processed: int, created: int, updated: int, skipped: int, errors: array<int, string>}
     */
    public function import(
        array $records,
        bool $dryRun = false,
        string $defaultAuthor = 'Autor nieznany',
        string $defaultCategory = 'Zbiory ogólne'
    ): array {
        $stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($records as $index => $raw) {
            ++$stats['processed'];
            try {
                $record = $this->normalizeRecord($raw);
                if ($record['isbn'] === null) {
                    $stats['errors'][] = sprintf('Wiersz %d: brak numeru ISBN', $index + 1);
                    continue;
                }

                $book = $this->books->findOneBy(['isbn' => $record['isbn']]);
                if ($book === null) {
                    if ($dryRun) {
                        ++$stats['created'];
                        continue;
                    }

                    $this->createBook($record, $defaultAuthor, $defaultCategory);
                    ++$stats['created'];
                    continue;
                }

                $changed = $this->updateBook($book, $record, $defaultCategory, $dryRun);
                if ($changed) {
                    if (!$dryRun) {
                        $this->entityManager->persist($book);
                    }
                    ++$stats['updated'];
                } else {
                    ++$stats['skipped'];
                }
            } catch (\Throwable $exception) {
                $stats['errors'][] = sprintf(
                    'Wiersz %d (%s): %s',
                    $index + 1,
                    $raw['isbn'] ?? 'n/a',
                    $exception->getMessage()
                );
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        return $stats;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function createBook(array $record, string $defaultAuthor, string $defaultCategory): Book
    {
        $book = (new Book())
            ->setTitle($record['title'] ?? ('Pozycja ' . $record['isbn']))
            ->setIsbn($record['isbn']);

        $authorName = $record['author'] ?? $defaultAuthor;
        $author = $this->resolveAuthor($authorName);
        $book->setAuthor($author);

        $categoryName = $record['category'] ?? $defaultCategory;
        $category = $this->resolveCategory($categoryName);
        $book->addCategory($category);

        if ($record['publisher'] !== null) {
            $book->setPublisher($record['publisher']);
        }
        if ($record['year'] !== null) {
            $book->setPublicationYear($record['year']);
        }
        if ($record['description'] !== null) {
            $book->setDescription($record['description']);
        }
        if ($record['resourceType'] !== null) {
            $book->setResourceType($record['resourceType']);
        }
        if ($record['signature'] !== null) {
            $book->setSignature($record['signature']);
        }

        $this->entityManager->persist($book);

        return $book;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function updateBook(Book $book, array $record, string $defaultCategory, bool $dryRun): bool
    {
        $changed = false;

        if ($record['title'] !== null && $book->getTitle() !== $record['title']) {
            $changed = true;
            if (!$dryRun) {
                $book->setTitle($record['title']);
            }
        }

        if ($record['publisher'] !== null && $book->getPublisher() !== $record['publisher']) {
            $changed = true;
            if (!$dryRun) {
                $book->setPublisher($record['publisher']);
            }
        }

        if ($record['year'] !== null && $book->getPublicationYear() !== $record['year']) {
            $changed = true;
            if (!$dryRun) {
                $book->setPublicationYear($record['year']);
            }
        }

        if ($record['description'] !== null && $book->getDescription() !== $record['description']) {
            $changed = true;
            if (!$dryRun) {
                $book->setDescription($record['description']);
            }
        }

        if ($record['resourceType'] !== null && $book->getResourceType() !== $record['resourceType']) {
            $changed = true;
            if (!$dryRun) {
                $book->setResourceType($record['resourceType']);
            }
        }

        if ($record['signature'] !== null && $book->getSignature() !== $record['signature']) {
            $changed = true;
            if (!$dryRun) {
                $book->setSignature($record['signature']);
            }
        }

        if ($record['author'] !== null && $book->getAuthor()->getName() !== $record['author']) {
            $changed = true;
            if (!$dryRun) {
                $author = $this->resolveAuthor($record['author']);
                $book->setAuthor($author);
            }
        }

        $categoryName = $record['category'];
        if ($categoryName === null && $book->getCategories()->count() === 0) {
            $categoryName = $defaultCategory;
        }
        if ($categoryName !== null && !$this->bookHasCategory($book, $categoryName)) {
            $changed = true;
            if (!$dryRun) {
                $category = $this->resolveCategory($categoryName);
                $book->addCategory($category);
            }
        }

        return $changed;
    }

    private function resolveAuthor(string $name): Author
    {
        $normalized = $this->normalizeString($name) ?? 'Autor nieznany';
        $author = $this->authors->findOneBy(['name' => $normalized]);
        if ($author instanceof Author) {
            return $author;
        }

        $author = (new Author())->setName($normalized);
        $this->entityManager->persist($author);

        return $author;
    }

    private function resolveCategory(string $name): Category
    {
        $normalized = $this->normalizeString($name) ?? 'Zbiory ogólne';
        $category = $this->categories->findOneBy(['name' => $normalized]);
        if ($category instanceof Category) {
            return $category;
        }

        $category = (new Category())->setName($normalized);
        $this->entityManager->persist($category);

        return $category;
    }

    private function bookHasCategory(Book $book, string $name): bool
    {
        $normalized = $this->normalizeString($name);
        if ($normalized === null) {
            return false;
        }

        foreach ($book->getCategories() as $category) {
            if (strcasecmp($category->getName(), $normalized) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{
     *     isbn: ?string,
     *     title: ?string,
     *     author: ?string,
     *     publisher: ?string,
     *     year: ?int,
     *     description: ?string,
     *     category: ?string,
     *     resourceType: ?string,
     *     signature: ?string
     * }
     */
    private function normalizeRecord(array $raw): array
    {
        $year = $raw['year'] ?? $raw['publicationYear'] ?? null;
        $year = is_numeric($year) ? (int) $year : null;

        return [
            'isbn' => $this->normalizeIsbn($raw['isbn'] ?? null),
            'title' => $this->normalizeString($raw['title'] ?? null),
            'author' => $this->normalizeString($raw['author'] ?? null),
            'publisher' => $this->normalizeString($raw['publisher'] ?? null),
            'year' => $year,
            'description' => $this->normalizeString($raw['description'] ?? null),
            'category' => $this->normalizeString($raw['category'] ?? null),
            'resourceType' => $this->normalizeString($raw['resourceType'] ?? null),
            'signature' => $this->normalizeString($raw['signature'] ?? null),
        ];
    }

    private function normalizeIsbn(mixed $isbn): ?string
    {
        if ($isbn === null) {
            return null;
        }

        $value = trim((string) $isbn);
        if ($value === '') {
            return null;
        }

        return $value;
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }
}
