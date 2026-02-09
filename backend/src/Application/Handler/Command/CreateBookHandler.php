<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Book\CreateBookCommand;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Event\BookCreatedEvent;
use App\Repository\AuthorRepository;
use App\Repository\CategoryRepository;
use App\Service\User\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler(bus: 'command.bus')]
class CreateBookHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthorRepository $authorRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly NotificationService $notificationService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(CreateBookCommand $command): Book
    {
        $author = $this->authorRepository->find($command->authorId);
        if (!$author) {
            throw new NotFoundHttpException('Author not found');
        }

        $categories = [];
        if (!empty($command->categoryIds)) {
            $uniqueCategoryIds = array_unique(array_map('intval', $command->categoryIds));
            $categories = $this->categoryRepository->findBy(['id' => $uniqueCategoryIds]);
            if (count($categories) !== count($uniqueCategoryIds)) {
                throw new NotFoundHttpException('One or more categories not found');
            }
        }

        if (empty($categories)) {
            throw new BadRequestHttpException('At least one category is required');
        }

        $totalCopies = $command->totalCopies;
        $desiredAvailable = $command->availableCopies ?? $totalCopies;
        $desiredAvailable = max(0, min($desiredAvailable, $totalCopies));

        $book = (new Book())
            ->setTitle($command->title)
            ->setAuthor($author)
            ->setIsbn($command->isbn)
            ->setDescription($command->description);

        if ($command->publisher) {
            $book->setPublisher($command->publisher);
        }
        if ($command->publicationYear) {
            $book->setPublicationYear($command->publicationYear);
        }
        if ($command->resourceType) {
            $book->setResourceType($command->resourceType);
        }
        if ($command->signature) {
            $book->setSignature($command->signature);
        }
        if ($command->targetAgeGroup) {
            $book->setTargetAgeGroup($command->targetAgeGroup);
        }

        foreach ($categories as $category) {
            $book->addCategory($category);
        }

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        $codePrefix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        for ($i = 1; $i <= $totalCopies; $i++) {
            $copy = (new BookCopy())
                ->setBook($book)
                ->setInventoryCode(sprintf('B%s-%03d', $codePrefix, $i))
                ->setStatus($i <= $desiredAvailable ? BookCopy::STATUS_AVAILABLE : BookCopy::STATUS_MAINTENANCE);

            $book->addInventoryCopy($copy);
            $this->entityManager->persist($copy);
        }

        $book->recalculateInventoryCounters();
        $this->entityManager->flush();

        $this->notificationService->notifyNewBookAvailable($book);

        $this->eventDispatcher->dispatch(new BookCreatedEvent($book));

        return $book;
    }
}
