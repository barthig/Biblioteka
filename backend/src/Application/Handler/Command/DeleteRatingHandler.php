<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Rating\DeleteRatingCommand;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class DeleteRatingHandler
{
    public function __construct(
        private RatingRepository $ratingRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array{averageRating: float|null, ratingCount: int}
     */
    public function __invoke(DeleteRatingCommand $command): array
    {
        $rating = $this->ratingRepository->find($command->ratingId);
        if (!$rating) {
            throw new NotFoundHttpException('Rating not found');
        }

        if ($rating->getUser()->getId() !== $command->userId && !$command->isAdmin) {
            throw new AccessDeniedHttpException('Forbidden');
        }

        $bookId = $rating->getBook()->getId();
        $this->entityManager->remove($rating);
        $this->entityManager->flush();

        return [
            'averageRating' => $this->ratingRepository->getAverageRatingForBook($bookId),
            'ratingCount' => $this->ratingRepository->getRatingCountForBook($bookId)
        ];
    }
}
