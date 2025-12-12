<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Favorite\RemoveFavoriteCommand;
use App\Application\Handler\Command\RemoveFavoriteHandler;
use App\Entity\Favorite;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class RemoveFavoriteHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private FavoriteRepository $favoriteRepository;
    private RemoveFavoriteHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->favoriteRepository = $this->createMock(FavoriteRepository::class);
        $this->handler = new RemoveFavoriteHandler($this->entityManager, $this->favoriteRepository);
    }

    public function testRemoveFavoriteSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        
        $favorite = $this->createMock(Favorite::class);
        $favorite->method('getUser')->willReturn($user);
        
        $this->favoriteRepository->method('find')->with(1)->willReturn($favorite);
        $this->entityManager->expects($this->once())->method('remove')->with($favorite);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new RemoveFavoriteCommand(favoriteId: 1, userId: 1);
        ($this->handler)($command);

        $this->assertTrue(true);
    }

    public function testThrowsExceptionWhenFavoriteNotFound(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->favoriteRepository->method('find')->with(999)->willReturn(null);

        $command = new RemoveFavoriteCommand(favoriteId: 999, userId: 1);
        ($this->handler)($command);
    }
}
