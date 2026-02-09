<?php
declare(strict_types=1);

namespace App\Service\System;

use App\Entity\Book;
use FOS\ElasticaBundle\Finder\FinderInterface;

/**
 * Service for searching books using Elasticsearch
 * 
 * Note: Requires Elasticsearch to be installed and running on localhost:9200
 * Installation: https://www.elastic.co/downloads/elasticsearch
 */
class ElasticsearchService
{
    public function __construct(
        private ?FinderInterface $bookFinder = null
    ) {
    }

    /**
     * Search books by query string
     * 
     * @param string $query Search query
     * @param int $limit Maximum number of results
     * @return array<int, array<string, mixed>>
     */
    public function searchBooks(string $query, int $limit = 20): array
    {
        if (!$this->bookFinder) {
            // Elasticsearch not configured - return empty array
            return [];
        }

        try {
            $results = $this->bookFinder->find($query, $limit);
            return array_map(fn($book) => $this->transformBook($book), iterator_to_array($results));
        } catch (\Exception $e) {
            // Log error and return empty results
            error_log('Elasticsearch error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if Elasticsearch is available
     */
    public function isAvailable(): bool
    {
        return $this->bookFinder !== null;
    }

    /**
     * Transform book entity to array
     */
    /**
     * @param Book|array<string, mixed> $book
     * @return array<string, mixed>
     */
    private function transformBook($book): array
    {
        if (is_array($book)) {
            return $book;
        }

        return [
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor()->getName(),
            'isbn' => $book->getIsbn(),
        ];
    }
}


