<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Review\DeleteReviewCommand;
use App\Entity\Rating;
use App\Exception\AuthorizationException;
use App\Exception\NotFoundException;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class DeleteReviewHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReviewRepository $reviewRepository
    ) {
    }

    public function __invoke(DeleteReviewCommand $command): void
    {
        $review = $this->reviewRepository->find($command->reviewId);
        
        if (!$review) {
            throw NotFoundException::forEntity('Review', $command->reviewId);
        }

        // Authorization: user can delete own review, or librarian can delete any
        if (!$command->isLibrarian && $review->getUser()->getId() !== $command->userId) {
            throw AuthorizationException::notOwner();
        }

        $rating = $this->entityManager->getRepository(Rating::class)->findOneBy([
            'user' => $review->getUser(),
            'book' => $review->getBook(),
        ]);

        $this->entityManager->remove($review);
        if ($rating) {
            $this->entityManager->remove($rating);
        }
        $this->entityManager->flush();
    }
}
