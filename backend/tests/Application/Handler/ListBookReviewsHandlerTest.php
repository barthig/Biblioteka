<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListBookReviewsHandler;
use App\Application\Query\Review\ListBookReviewsQuery;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ListBookReviewsHandlerTest extends TestCase
{
    private ReviewRepository $reviewRepository;
    private EntityManagerInterface $entityManager;
    private ListBookReviewsHandler $handler;

    protected function setUp(): void
    {
        $this->reviewRepository = $this->createMock(ReviewRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new ListBookReviewsHandler($this->reviewRepository, $this->entityManager);
    }

    public function testListBookReviewsSuccess(): void
    {
        $book = $this->createMock(\App\Entity\Book::class);
        $bookRepo = $this->createMock(\App\Repository\BookRepository::class);
        $bookRepo->method('find')->with(1)->willReturn($book);
        
        $this->entityManager->method('getRepository')
            ->with(\App\Entity\Book::class)
            ->willReturn($bookRepo);
            
        $this->reviewRepository->method('getSummaryForBook')->willReturn(['count' => 0]);
        $this->reviewRepository->method('findByBook')->willReturn([]);

        $query = new ListBookReviewsQuery(bookId: 1);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
