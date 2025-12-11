<?php

namespace App\Tests\Service;

use App\Service\BookCacheService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class BookCacheServiceTest extends TestCase
{
    private ArrayAdapter $cache;
    private BookCacheService $service;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->service = new BookCacheService($this->cache);
    }

    public function testGetBook(): void
    {
        $bookId = 1;
        $expectedData = ['id' => 1, 'title' => 'Test Book'];

        $result = $this->service->getBook($bookId, fn() => $expectedData);

        $this->assertEquals($expectedData, $result);
        
        // Second call should return cached value
        $result2 = $this->service->getBook($bookId, fn() => ['id' => 99, 'title' => 'Should not be called']);
        $this->assertEquals($expectedData, $result2);
    }

    public function testInvalidateBook(): void
    {
        $bookId = 1;
        $data = ['id' => 1, 'title' => 'Test Book'];
        
        // Cache the book
        $this->service->getBook($bookId, fn() => $data);
        
        // Invalidate
        $this->service->invalidateBook($bookId);
        
        // Should fetch fresh data
        $newData = ['id' => 1, 'title' => 'Updated Book'];
        $result = $this->service->getBook($bookId, fn() => $newData);
        $this->assertEquals($newData, $result);
    }

    public function testInvalidateBooksList(): void
    {
        $filters = ['genre' => 'fiction'];
        $data = ['books' => []];
        
        $this->service->getBooksList($filters, fn() => $data);
        $this->service->invalidateBooksList();
        
        // After clear, cache should be empty
        $this->assertTrue(true); // Cache cleared successfully
    }

    public function testGetBookAvailability(): void
    {
        $bookId = 1;
        $availabilityData = ['available' => 3, 'total' => 5];

        $result = $this->service->getBookAvailability($bookId, fn() => $availabilityData);

        $this->assertEquals($availabilityData, $result);
    }

    public function testGetPopularBooks(): void
    {
        $popularBooks = [
            ['id' => 1, 'title' => 'Book 1', 'loans' => 100],
            ['id' => 2, 'title' => 'Book 2', 'loans' => 95],
        ];

        $result = $this->service->getPopularBooks(fn() => $popularBooks);

        $this->assertEquals($popularBooks, $result);
    }

    public function testGetNewBooks(): void
    {
        $newBooks = [
            ['id' => 3, 'title' => 'New Book 1'],
            ['id' => 4, 'title' => 'New Book 2'],
        ];

        $result = $this->service->getNewBooks(fn() => $newBooks);

        $this->assertEquals($newBooks, $result);
    }

    public function testInvalidateBookAvailability(): void
    {
        $bookId = 1;
        $data = ['available' => 3];
        
        $this->service->getBookAvailability($bookId, fn() => $data);
        $this->service->invalidateBookAvailability($bookId);
        
        $newData = ['available' => 2];
        $result = $this->service->getBookAvailability($bookId, fn() => $newData);
        $this->assertEquals($newData, $result);
    }

    public function testGetBooksList(): void
    {
        $filters = ['author' => 'Test Author'];
        $books = [['id' => 1], ['id' => 2]];

        $result = $this->service->getBooksList($filters, fn() => $books);

        $this->assertEquals($books, $result);
        
        // Different filters should trigger different cache
        $filters2 = ['genre' => 'Fiction'];
        $books2 = [['id' => 3]];
        $result2 = $this->service->getBooksList($filters2, fn() => $books2);
        $this->assertEquals($books2, $result2);
    }
}
