<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Book\UpdateBookCommand;
use App\Entity\Book;
use App\Event\BookUpdatedEvent;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
class UpdateBookHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookRepository $bookRepository,
        private readonly AuthorRepository $authorRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(UpdateBookCommand $command): Book
    {
        $book = $this->bookRepository->find($command->bookId);
        
        if (!$book) {
            throw new NotFoundHttpException('Book not found');
        }

        if ($command->title !== null) {
            $book->setTitle($command->title);
        }

        if ($command->authorId !== null) {
            $author = $this->authorRepository->find($command->authorId);
            if (!$author) {
                throw new NotFoundHttpException('Author not found');
            }
            $book->setAuthor($author);
        }

        if ($command->categoryIds !== null) {
            if (empty($command->categoryIds)) {
                throw new BadRequestHttpException('At least one category is required');
            }
            $uniqueCategoryIds = array_unique(array_map('intval', $command->categoryIds));
            $categories = $this->categoryRepository->findBy(['id' => $uniqueCategoryIds]);
            if (count($categories) !== count($uniqueCategoryIds)) {
                throw new NotFoundHttpException('One or more categories not found');
            }
            $book->clearCategories();
            foreach ($categories as $category) {
                $book->addCategory($category);
            }
        }

        if ($command->description !== null) {
            $book->setDescription($command->description);
        }

        if ($command->isbn !== null) {
            $book->setIsbn($command->isbn);
        }

        if ($command->publisher !== null) {
            $book->setPublisher($command->publisher);
        }

        if ($command->publicationYear !== null) {
            $book->setPublicationYear($command->publicationYear);
        }

        if ($command->resourceType !== null) {
            $book->setResourceType($command->resourceType);
        }

        if ($command->signature !== null) {
            $book->setSignature($command->signature);
        }

        if ($command->targetAgeGroup !== null) {
            $book->setTargetAgeGroup($command->targetAgeGroup);
        }

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new BookUpdatedEvent($book));

        return $book;
    }
}
