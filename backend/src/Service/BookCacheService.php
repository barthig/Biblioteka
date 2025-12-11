<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service for managing book-related caching operations
 */
class BookCacheService
{
    private const CACHE_KEY_BOOK = 'book_%d';
    private const CACHE_KEY_BOOKS_LIST = 'books_list_%s';
    private const CACHE_KEY_BOOK_AVAILABILITY = 'book_availability_%d';
    private const CACHE_KEY_POPULAR_BOOKS = 'popular_books';
    private const CACHE_KEY_NEW_BOOKS = 'new_books';

    public function __construct(
        private CacheInterface $booksCache
    ) {
    }

    /**
     * Get cached book data
     */
    public function getBook(int $bookId, callable $callback): mixed
    {
        return $this->booksCache->get(
            sprintf(self::CACHE_KEY_BOOK, $bookId),
            function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(7200); // 2 hours
                return $callback();
            }
        );
    }

    /**
     * Get cached books list
     */
    public function getBooksList(array $filters, callable $callback): mixed
    {
        $cacheKey = sprintf(self::CACHE_KEY_BOOKS_LIST, md5(serialize($filters)));
        
        return $this->booksCache->get(
            $cacheKey,
            function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(3600); // 1 hour
                return $callback();
            }
        );
    }

    /**
     * Get cached book availability
     */
    public function getBookAvailability(int $bookId, callable $callback): mixed
    {
        return $this->booksCache->get(
            sprintf(self::CACHE_KEY_BOOK_AVAILABILITY, $bookId),
            function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(300); // 5 minutes - shorter because availability changes often
                return $callback();
            }
        );
    }

    /**
     * Get cached popular books
     */
    public function getPopularBooks(callable $callback): mixed
    {
        return $this->booksCache->get(
            self::CACHE_KEY_POPULAR_BOOKS,
            function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(3600); // 1 hour
                return $callback();
            }
        );
    }

    /**
     * Get cached new books
     */
    public function getNewBooks(callable $callback): mixed
    {
        return $this->booksCache->get(
            self::CACHE_KEY_NEW_BOOKS,
            function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(1800); // 30 minutes
                return $callback();
            }
        );
    }

    /**
     * Invalidate book cache
     */
    public function invalidateBook(int $bookId): void
    {
        $this->booksCache->delete(sprintf(self::CACHE_KEY_BOOK, $bookId));
        $this->booksCache->delete(sprintf(self::CACHE_KEY_BOOK_AVAILABILITY, $bookId));
    }

    /**
     * Invalidate books list cache
     * Note: This invalidates all cached book lists
     */
    public function invalidateBooksList(): void
    {
        // Note: CacheInterface doesn't have clear(), need to delete specific keys
        // In production, consider using TagAwareCacheInterface for better invalidation
        $this->booksCache->delete(self::CACHE_KEY_POPULAR_BOOKS);
        $this->booksCache->delete(self::CACHE_KEY_NEW_BOOKS);
    }

    /**
     * Invalidate book availability cache
     */
    public function invalidateBookAvailability(int $bookId): void
    {
        $this->booksCache->delete(sprintf(self::CACHE_KEY_BOOK_AVAILABILITY, $bookId));
    }

    /**
     * Invalidate popular books cache
     */
    public function invalidatePopularBooks(): void
    {
        $this->booksCache->delete(self::CACHE_KEY_POPULAR_BOOKS);
    }

    /**
     * Invalidate new books cache
     */
    public function invalidateNewBooks(): void
    {
        $this->booksCache->delete(self::CACHE_KEY_NEW_BOOKS);
    }
}
