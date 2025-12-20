<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Favorite\AddFavoriteCommand;
use App\Application\Handler\Command\AddFavoriteHandler;
use App\Entity\Book;
use App\Entity\Favorite;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\FavoriteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AddFavoriteHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private BookRepository $bookRepository;
    private UserRepository $userRepository;
    private FavoriteRepository $favoriteRepository;
    private AddFavoriteHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->favoriteRepository = $this->createMock(FavoriteRepository::class);
        $this->handler = new AddFavoriteHandler(
            $this->entityManager,
            $this->favoriteRepository
        );
    }

    public function testAddFavoriteSuccess(): void
    {
        // Mock Entity Manager's getRepository to return our mocked repositories
        $userRepo = $this->createMock(\App\Repository\UserRepository::class);
        $bookRepo = $this->createMock(\App\Repository\BookRepository::class);
        $interactionRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);

        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);

        $userRepo->method('find')->with(1)->willReturn($user);
        $bookRepo->method('find')->with(1)->willReturn($book);
        $interactionRepo->method('findOneBy')->willReturn(null);

        $this->entityManager->method('getRepository')
            ->willReturnCallback(function($class) use ($userRepo, $bookRepo, $interactionRepo) {
                if ($class === User::class) return $userRepo;
                if ($class === Book::class) return $bookRepo;
                if ($class === \App\Entity\UserBookInteraction::class) return $interactionRepo;
                return $this->createMock(\Doctrine\ORM\EntityRepository::class);
            });
        
        $this->favoriteRepository->method('findOneBy')->willReturn(null);
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new AddFavoriteCommand(bookId: 1, userId: 1);

        $result = ($this->handler)($command);

        $this->assertInstanceOf(Favorite::class, $result);
    }
}
