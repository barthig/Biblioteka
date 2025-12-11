<?php

namespace App\Tests\Performance;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Performance tests for Book-related operations
 * 
 * These tests ensure that database queries and operations
 * complete within acceptable time limits.
 */
class BookPerformanceTest extends KernelTestCase
{
    private BookRepository $bookRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bookRepository = static::getContainer()->get(BookRepository::class);
    }

    /**
     * Test that finding all books completes in reasonable time
     */
    public function testFindAllBooksPerformance(): void
    {
        $start = microtime(true);
        
        $books = $this->bookRepository->findAll();
        
        $durationMs = (microtime(true) - $start) * 1000;

        // Should complete in less than 500ms even with large dataset
        $this->assertLessThan(500, $durationMs, 
            sprintf('Finding all books took %.2fms, should be under 500ms', $durationMs)
        );
        
        // Verify we got results
        $this->assertIsArray($books);
    }

    /**
     * Test that finding a single book is fast
     */
    public function testFindBookByIdPerformance(): void
    {
        $start = microtime(true);
        
        $book = $this->bookRepository->find(1);
        
        $durationMs = (microtime(true) - $start) * 1000;

        // Single record lookup should be very fast
        $this->assertLessThan(50, $durationMs,
            sprintf('Finding book by ID took %.2fms, should be under 50ms', $durationMs)
        );
    }

    /**
     * Test search performance
     */
    public function testBookSearchPerformance(): void
    {
        $start = microtime(true);
        
        $results = $this->bookRepository->createQueryBuilder('b')
            ->where('b.title LIKE :search')
            ->setParameter('search', '%test%')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
        
        $durationMs = (microtime(true) - $start) * 1000;

        // Search with LIKE should complete reasonably fast
        $this->assertLessThan(200, $durationMs,
            sprintf('Book search took %.2fms, should be under 200ms', $durationMs)
        );
        
        $this->assertIsArray($results);
    }

    /**
     * Test that we can handle pagination efficiently
     */
    public function testPaginationPerformance(): void
    {
        $start = microtime(true);
        
        $books = $this->bookRepository->createQueryBuilder('b')
            ->setMaxResults(20)
            ->setFirstResult(0)
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
        
        $durationMs = (microtime(true) - $start) * 1000;

        // Paginated query should be fast
        $this->assertLessThan(100, $durationMs,
            sprintf('Pagination took %.2fms, should be under 100ms', $durationMs)
        );
        
        $this->assertCount(min(20, count($books)), $books);
    }

    /**
     * Test memory usage doesn't spike
     */
    public function testMemoryUsage(): void
    {
        $memoryBefore = memory_get_usage();
        
        // Perform some operations
        $books = $this->bookRepository->findAll();
        $count = count($books);
        
        $memoryAfter = memory_get_usage();
        $memoryUsedMB = ($memoryAfter - $memoryBefore) / 1024 / 1024;

        // Should not use more than 10MB for normal operations
        $this->assertLessThan(10, $memoryUsedMB,
            sprintf('Memory usage was %.2fMB, should be under 10MB', $memoryUsedMB)
        );
    }
}
