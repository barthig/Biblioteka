<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Recommendation\UpsertRecommendationFeedbackCommand;
use App\Entity\RecommendationFeedback;
use App\Repository\BookRepository;
use App\Repository\RecommendationFeedbackRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class UpsertRecommendationFeedbackHandler
{
    public function __construct(
        private RecommendationFeedbackRepository $feedbackRepository,
        private BookRepository $bookRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(UpsertRecommendationFeedbackCommand $command): void
    {
        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $book = $this->bookRepository->find($command->bookId);
        if (!$book) {
            throw new NotFoundHttpException('Book not found');
        }

        if (!in_array($command->feedbackType, [RecommendationFeedback::TYPE_DISMISS, RecommendationFeedback::TYPE_INTERESTED], true)) {
            throw new BadRequestHttpException('Invalid feedbackType');
        }

        $existing = $this->feedbackRepository->findOneBy(['user' => $user, 'book' => $book]);

        if ($existing) {
            $existing->setFeedbackType($command->feedbackType);
        } else {
            $feedback = new RecommendationFeedback();
            $feedback->setUser($user)
                ->setBook($book)
                ->setFeedbackType($command->feedbackType);
            $this->entityManager->persist($feedback);
        }

        $this->entityManager->flush();
    }
}
