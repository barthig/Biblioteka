<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Favorite\AddFavoriteCommand;
use App\Entity\Book;
use App\Entity\Favorite;
use App\Entity\User;
use App\Entity\UserBookInteraction;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AddFavoriteHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FavoriteRepository $favoriteRepository
    ) {
    }

    public function __invoke(AddFavoriteCommand $command): Favorite
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        $bookRepo = $this->entityManager->getRepository(Book::class);

        $user = $userRepo->find($command->userId);
        $book = $bookRepo->find($command->bookId);

        if (!$user || !$book) {
            throw new NotFoundHttpException('User or book not found');
        }

        if ($this->favoriteRepository->findOneByUserAndBook($user, $book)) {
            throw new ConflictHttpException('Książka znajduje się już na Twojej półce');
        }

        $favorite = (new Favorite())
            ->setUser($user)
            ->setBook($book);

        $this->entityManager->persist($favorite);

        $interactionRepo = $this->entityManager->getRepository(UserBookInteraction::class);
        $interaction = $interactionRepo->findOneBy(['user' => $user, 'book' => $book]);
        if (!$interaction) {
            $interaction = (new UserBookInteraction())
                ->setUser($user)
                ->setBook($book);
            $this->entityManager->persist($interaction);
        }
        $interaction->setType(UserBookInteraction::TYPE_LIKED);

        $this->entityManager->flush();

        return $favorite;
    }
}
