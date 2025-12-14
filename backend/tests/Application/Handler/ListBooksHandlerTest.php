<?php
namespace App\Tests\Application\Handler;

use App\Application\Query\Book\ListBooksQuery;
use App\Application\Handler\Query\ListBooksHandler;
use App\Repository\BookRepository;
use App\Repository\RatingRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ListBooksHandlerTest extends TestCase
{
    private BookRepository $bookRepository;
    private ManagerRegistry $doctrine;
    private RatingRepository $ratingRepository;
    private ListBooksHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->ratingRepository = $this->createMock(RatingRepository::class);
        $this->handler = new ListBooksHandler($this->bookRepository, $this->doctrine, $this->ratingRepository);
    }

    public function testListBooksSuccess(): void
    {
        $this->bookRepository
            ->method('searchPublic')
            ->willReturn([
                'data' => [],
                'meta' => [
                    'page' => 1,
                    'limit' => 20,
                    'total' => 0,
                    'totalPages' => 0,
                ],
            ]);

        $query = new ListBooksQuery(page: 1, limit: 20);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }

    public function testListBooksWithPagination(): void
    {
        $this->bookRepository
            ->method('searchPublic')
            ->with($this->callback(function (array $filters): bool {
                return $filters['page'] === 3 && $filters['limit'] === 10;
            }))
            ->willReturn([
                'data' => [],
                'meta' => [
                    'page' => 3,
                    'limit' => 10,
                    'total' => 0,
                    'totalPages' => 0,
                ],
            ]);

        $query = new ListBooksQuery(page: 3, limit: 10);
        $result = ($this->handler)($query);
        
        $this->assertIsArray($result);
    }
}
