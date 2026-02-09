<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Review\CreateReviewCommand;
use App\Entity\Book;
use App\Entity\Rating;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class CreateReviewHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReviewRepository $reviewRepository
    ) {
    }

    public function __invoke(CreateReviewCommand $command): Review
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        $bookRepo = $this->entityManager->getRepository(Book::class);

        $user = $userRepo->find($command->userId);
        $book = $bookRepo->find($command->bookId);

        if (!$user || !$book) {
            throw new NotFoundHttpException('User or book not found');
        }

        $review = $this->reviewRepository->findOneByUserAndBook($user, $book);

        if (!$review) {
            $review = (new Review())
                ->setBook($book)
                ->setUser($user);
        }

        $review->setRating($command->rating)
               ->setComment($command->comment)
               ->touch();

        $this->entityManager->persist($review);

        $ratingRepo = $this->entityManager->getRepository(Rating::class);
        $rating = $ratingRepo->findOneBy(['user' => $user, 'book' => $book]);
        if (!$rating) {
            $rating = (new Rating())
                ->setBook($book)
                ->setUser($user);
        }
        $rating->setRating($command->rating);
        $this->entityManager->persist($rating);
        $this->entityManager->flush();

        return $review;
    }
}
