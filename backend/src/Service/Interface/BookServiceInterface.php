<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Entity\Book;
use App\Entity\User;

/**
 * Interface for Book-related business operations
 */
interface BookServiceInterface
{
    /**
     * Get a book by ID with availability information
     */
    public function getBookWithAvailability(int $bookId): ?array;

    /**
     * Search books with filters
     * 
     * @param array $filters Search filters (title, author, category, etc.)
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array{data: array, total: int, page: int, limit: int}
     */
    public function searchBooks(array $filters, int $page = 1, int $limit = 20): array;

    /**
     * Get newest books
     */
    public function getNewestBooks(int $limit = 10): array;

    /**
     * Get popular books by loan count
     */
    public function getPopularBooks(int $limit = 10): array;

    /**
     * Get recommended books for a user
     */
    public function getRecommendedBooks(User $user, int $limit = 10): array;

    /**
     * Check if a book is available for loan
     */
    public function isAvailable(Book $book): bool;

    /**
     * Get available copies count
     */
    public function getAvailableCopiesCount(Book $book): int;
}
