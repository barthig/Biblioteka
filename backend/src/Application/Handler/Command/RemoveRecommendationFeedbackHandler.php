<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Recommendation\RemoveRecommendationFeedbackCommand;
use App\Repository\RecommendationFeedbackRepository;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class RemoveRecommendationFeedbackHandler
{
    public function __construct(
        private RecommendationFeedbackRepository $feedbackRepository,
        private BookRepository $bookRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(RemoveRecommendationFeedbackCommand $command): void
    {
        $user = $this->userRepository->find($command->userId);
        $book = $this->bookRepository->find($command->bookId);

        if (!$user || !$book) {
            throw new NotFoundHttpException('Not found');
        }

        $feedback = $this->feedbackRepository->findOneBy(['user' => $user, 'book' => $book]);
        if ($feedback) {
            $this->entityManager->remove($feedback);
            $this->entityManager->flush();
        }
    }
}
