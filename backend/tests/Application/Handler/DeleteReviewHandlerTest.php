<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Review\DeleteReviewCommand;
use App\Application\Handler\Command\DeleteReviewHandler;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteReviewHandlerTest extends TestCase
{
    private ReviewRepository $reviewRepository;
    private EntityManagerInterface $entityManager;
    private DeleteReviewHandler $handler;

    protected function setUp(): void
    {
        $this->reviewRepository = $this->createMock(ReviewRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new DeleteReviewHandler($this->entityManager, $this->reviewRepository);
    }

    public function testDeleteReviewSuccess(): void
    {
        $review = $this->createMock(Review::class);
        $this->reviewRepository->method('find')->with(1)->willReturn($review);
        $this->entityManager->expects($this->once())->method('remove')->with($review);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new DeleteReviewCommand(reviewId: 1, userId: 1, isLibrarian: true);
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
