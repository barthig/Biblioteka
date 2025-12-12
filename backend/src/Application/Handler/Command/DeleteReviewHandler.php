<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Review\DeleteReviewCommand;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteReviewHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReviewRepository $reviewRepository
    ) {
    }

    public function __invoke(DeleteReviewCommand $command): void
    {
        $review = $this->reviewRepository->find($command->reviewId);
        
        if (!$review) {
            throw new \RuntimeException('Review not found');
        }

        // Authorization: user can delete own review, or librarian can delete any
        if (!$command->isLibrarian && $review->getUser()->getId() !== $command->userId) {
            throw new \RuntimeException('Forbidden: You can only delete your own reviews');
        }

        $this->em->remove($review);
        $this->em->flush();
    }
}
