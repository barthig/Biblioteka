<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Rating\RateBookCommand;
use App\Entity\Rating;
use App\Entity\UserBookInteraction;
use App\Repository\BookRepository;
use App\Repository\RatingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class RateBookHandler
{
    public function __construct(
        private RatingRepository $ratingRepository,
        private BookRepository $bookRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array{
     *   rating: array{id: int|null, rating: int, review: string|null},
     *   averageRating: float|null,
     *   ratingCount: int
     * }
     */
    public function __invoke(RateBookCommand $command): array
    {
        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $book = $this->bookRepository->find($command->bookId);
        if (!$book) {
            throw new NotFoundHttpException('Book not found');
        }

        if ($command->rating < 1 || $command->rating > 5) {
            throw new BadRequestHttpException('Rating must be between 1 and 5');
        }

        $existingRating = $this->ratingRepository->findOneBy(['user' => $user, 'book' => $book]);

        if ($existingRating) {
            $existingRating->setRating($command->rating);
            if ($command->review !== null) {
                $existingRating->setReview($command->review);
            }
        } else {
            $existingRating = (new Rating())
                ->setUser($user)
                ->setBook($book)
                ->setRating($command->rating);
            if ($command->review) {
                $existingRating->setReview($command->review);
            }
            $this->entityManager->persist($existingRating);
        }

        $interactionRepo = $this->entityManager->getRepository(UserBookInteraction::class);
        $interaction = $interactionRepo->findOneBy(['user' => $user, 'book' => $book]);
        if (!$interaction) {
            $interaction = (new UserBookInteraction())
                ->setUser($user)
                ->setBook($book);
            $this->entityManager->persist($interaction);
        }
        $interaction->setRating($command->rating);
        $interactionType = $command->rating >= 4
            ? UserBookInteraction::TYPE_LIKED
            : UserBookInteraction::TYPE_READ;
        $interaction->setType($interactionType);

        $this->entityManager->flush();

        return [
            'rating' => [
                'id' => $existingRating->getId(),
                'rating' => $existingRating->getRating(),
                'review' => $existingRating->getReview(),
            ],
            'averageRating' => $this->ratingRepository->getAverageRatingForBook($book->getId()),
            'ratingCount' => $this->ratingRepository->getRatingCountForBook($book->getId())
        ];
    }
}
