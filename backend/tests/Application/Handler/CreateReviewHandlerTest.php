<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Review\CreateReviewCommand;
use App\Application\Handler\Command\CreateReviewHandler;
use App\Entity\Book;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateReviewHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private BookRepository $bookRepository;
    private UserRepository $userRepository;
    private CreateReviewHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new CreateReviewHandler($this->entityManager, $this->bookRepository, $this->userRepository);
    }

    public function testCreateReviewSuccess(): void
    {
        $book = $this->createMock(Book::class);
        $user = $this->createMock(User::class);
        
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->userRepository->method('find')->with(1)->willReturn($user);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateReviewCommand(
            bookId: 1,
            userId: 1,
            rating: 5,
            comment: 'Great book!'
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(Review::class, $result);
    }
}
