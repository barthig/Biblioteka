<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListBookReviewsHandler;
use App\Application\Query\Review\ListBookReviewsQuery;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ListBookReviewsHandlerTest extends TestCase
{
    private RatingRepository $ratingRepository;
    private EntityManagerInterface $entityManager;
    private ListBookReviewsHandler $handler;

    protected function setUp(): void
    {
        $this->ratingRepository = $this->createMock(RatingRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new ListBookReviewsHandler($this->ratingRepository, $this->entityManager);
    }

    public function testListBookReviewsSuccess(): void
    {
        $book = $this->createMock(\App\Entity\Book::class);
        $book->method('getId')->willReturn(1);
        $bookRepo = $this->createMock(\App\Repository\BookRepository::class);
        $bookRepo->method('find')->with(1)->willReturn($book);
        
        $this->entityManager->method('getRepository')
            ->with(\App\Entity\Book::class)
            ->willReturn($bookRepo);
            
        $this->ratingRepository->method('getAverageRatingForBook')->willReturn(null);
        $this->ratingRepository->method('getRatingCountForBook')->willReturn(0);
        $this->ratingRepository->method('findByBook')->willReturn([]);

        $query = new ListBookReviewsQuery(bookId: 1);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
