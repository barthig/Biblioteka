<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Favorite\RemoveFavoriteCommand;
use App\Entity\Favorite;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveFavoriteHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FavoriteRepository $favoriteRepository
    ) {
    }

    public function __invoke(RemoveFavoriteCommand $command): void
    {
        $favorite = $this->favoriteRepository->find($command->favoriteId);

        if (!$favorite) {
            throw new NotFoundHttpException('Favorite not found');
        }

        if ($favorite->getUser()->getId() !== $command->userId) {
            throw new AccessDeniedHttpException('You can only remove your own favorites');
        }

        $this->entityManager->remove($favorite);
        $this->entityManager->flush();
    }
}
