<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Favorite\AddFavoriteCommand;
use App\Application\Handler\Command\AddFavoriteHandler;
use App\Entity\Book;
use App\Entity\Favorite;
use App\Entity\User;
use App\Entity\UserBookInteraction;
use App\Event\FavoriteAddedEvent;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AddFavoriteHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private FavoriteRepository $favoriteRepository;
    private EventDispatcherInterface $eventDispatcher;
    private AddFavoriteHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->favoriteRepository = $this->createMock(FavoriteRepository::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->handler = new AddFavoriteHandler(
            $this->entityManager,
            $this->favoriteRepository,
            $this->eventDispatcher
        );
    }

    public function testAddFavoriteSuccess(): void
    {
        $userRepo = $this->createMock(EntityRepository::class);
        $bookRepo = $this->createMock(EntityRepository::class);
        $interactionRepo = $this->createMock(EntityRepository::class);

        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);

        $userRepo->method('find')->with(1)->willReturn($user);
        $bookRepo->method('find')->with(1)->willReturn($book);
        $interactionRepo->method('findOneBy')->with(['user' => $user, 'book' => $book])->willReturn(null);

        $this->entityManager->method('getRepository')
            ->willReturnCallback(function (string $class) use ($userRepo, $bookRepo, $interactionRepo) {
                return match ($class) {
                    User::class => $userRepo,
                    Book::class => $bookRepo,
                    UserBookInteraction::class => $interactionRepo,
                    default => $this->createMock(EntityRepository::class),
                };
            });

        $this->favoriteRepository->method('findOneByUserAndBook')->with($user, $book)->willReturn(null);
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(FavoriteAddedEvent::class));

        $command = new AddFavoriteCommand(bookId: 1, userId: 1);

        $result = ($this->handler)($command);

        $this->assertInstanceOf(Favorite::class, $result);
    }
}
