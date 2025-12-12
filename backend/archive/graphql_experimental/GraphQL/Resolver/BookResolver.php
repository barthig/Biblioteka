<?php

namespace App\GraphQL\Resolver;

use App\Repository\BookRepository;
use App\Entity\Book;

class BookResolver
{
    public function __construct(
        private BookRepository $bookRepository
    ) {
    }

    /**
     * Get list of books with optional filtering
     */
    public function getBooks(array $args): array
    {
        $limit = $args['limit'] ?? 20;
        $offset = $args['offset'] ?? 0;
        $search = $args['search'] ?? null;

        $qb = $this->bookRepository->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('b.id', 'DESC');

        if ($search) {
            $qb->where('b.title LIKE :search OR a.name LIKE :search OR b.isbn LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $books = $qb->getQuery()->getResult();

        return array_map(fn(Book $book) => $this->bookToArray($book), $books);
    }

    /**
     * Get single book by ID
     */
    public function getBook(int $id): ?array
    {
        $book = $this->bookRepository->find($id);
        
        if (!$book) {
            return null;
        }

        return $this->bookToArray($book);
    }

    /**
     * Convert Book entity to array for GraphQL
     */
    private function bookToArray(Book $book): array
    {
        return [
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'isbn' => $book->getIsbn(),
            'author' => $book->getAuthor()?->getName() ?? 'Unknown',
            'publisher' => $book->getPublisher()?->getName() ?? null,
            'publicationYear' => $book->getPublicationYear(),
            'category' => $book->getCategories()->first() ? $book->getCategories()->first()->getName() : null,
            'description' => $book->getDescription(),
            'totalCopies' => count($book->getInventory()),
            'availableCopies' => count($book->getInventory()->filter(fn($copy) => $copy->getStatus() === 'available')),
            'createdAt' => $book->getCreatedAt()?->format('c'),
            'updatedAt' => null,
        ];
    }
}
