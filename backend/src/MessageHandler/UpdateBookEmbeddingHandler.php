<?php
declare(strict_types=1);
namespace App\MessageHandler;

use App\Entity\Book;
use App\Message\UpdateBookEmbeddingMessage;
use App\Service\Book\OpenAIEmbeddingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateBookEmbeddingHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OpenAIEmbeddingService $embeddingService
    ) {
    }

    public function __invoke(UpdateBookEmbeddingMessage $message): void
    {
        $book = $this->entityManager->getRepository(Book::class)->find($message->getBookId());
        if (!$book) {
            return;
        }

        $title = trim($book->getTitle());
        $description = $book->getDescription() ? trim($book->getDescription()) : '';
        $text = $description !== '' ? $title . "\n\n" . $description : $title;
        if ($text === '') {
            return;
        }

        $embedding = $this->embeddingService->getVector($text);
        $book->setEmbedding($embedding);

        $this->entityManager->persist($book);
        $this->entityManager->flush();
    }
}
