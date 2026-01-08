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
        $this->handler = new CreateReviewHandler($this->entityManager, $this->createMock(\App\Repository\ReviewRepository::class));
    }

    public function testCreateReviewSuccess(): void
    {
        // Mock Entity Manager's getRepository to return our mocked repositories
        $userRepo = $this->createMock(\App\Repository\UserRepository::class);
        $bookRepo = $this->createMock(\App\Repository\BookRepository::class);
        $ratingRepo = $this->createMock(\App\Repository\RatingRepository::class);
        
        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);
        
        $userRepo->method('find')->with(1)->willReturn($user);
        $bookRepo->method('find')->with(1)->willReturn($book);
        
        $ratingRepo->method('findOneBy')->with(['user' => $user, 'book' => $book])->willReturn(null);

        $this->entityManager->method('getRepository')
            ->willReturnCallback(function($class) use ($userRepo, $bookRepo, $ratingRepo) {
                if ($class === User::class) return $userRepo;
                if ($class === Book::class) return $bookRepo;
                if ($class === \App\Entity\Rating::class) return $ratingRepo;
                return $this->createMock(\Doctrine\ORM\EntityRepository::class);
            });
        
        $this->entityManager->expects($this->exactly(2))->method('persist');
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
