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
            $this->bookRepository,
            $this->userRepository,
            $this->favoriteRepository
        );
    }

    public function testAddFavoriteSuccess(): void
    {
        $book = $this->createMock(Book::class);
        $user = $this->createMock(User::class);
        
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->userRepository->method('find')->with(1)->willReturn($user);
        $this->favoriteRepository->method('findOneBy')->willReturn(null);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new AddFavoriteCommand(bookId: 1, userId: 1);

        $result = ($this->handler)($command);

        $this->assertInstanceOf(Favorite::class, $result);
    }
}
