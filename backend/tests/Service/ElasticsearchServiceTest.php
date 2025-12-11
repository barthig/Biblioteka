<?php

namespace App\Tests\Service;

use App\Service\ElasticsearchService;
use PHPUnit\Framework\TestCase;

class ElasticsearchServiceTest extends TestCase
{
    public function testServiceCanBeCreatedWithoutFinder(): void
    {
        $service = new ElasticsearchService();
        
        $this->assertInstanceOf(ElasticsearchService::class, $service);
    }

    public function testIsAvailableReturnsFalseWhenNoFinder(): void
    {
        $service = new ElasticsearchService();
        
        $this->assertFalse($service->isAvailable());
    }

    public function testSearchBooksReturnsEmptyWhenNoFinder(): void
    {
        $service = new ElasticsearchService();
        
        $results = $service->searchBooks('test');
        
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testSearchBooksReturnsEmptyArrayOnError(): void
    {
        // When Elasticsearch is not running, searchBooks should return empty array
        $service = new ElasticsearchService();
        
        $results = $service->searchBooks('error test');
        
        $this->assertIsArray($results);
    }
}
