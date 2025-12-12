<?php
namespace App\Application\Handler\Command;

use App\Application\Command\BookInventory\UpdateBookCopyCommand;
use App\Entity\BookCopy;
use App\Repository\BookRepository;
use App\Repository\BookCopyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateBookCopyHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookRepository $bookRepository,
        private readonly BookCopyRepository $bookCopyRepository
    ) {
    }

    public function __invoke(UpdateBookCopyCommand $command): BookCopy
    {
        $book = $this->bookRepository->find($command->bookId);
        if (!$book) {
            throw new \RuntimeException('Book not found');
        }

        $copy = $this->bookCopyRepository->find($command->copyId);
        if (!$copy || $copy->getBook()->getId() !== $book->getId()) {
            throw new \RuntimeException('Inventory copy not found');
        }

        if ($command->status !== null) {
            try {
                $copy->setStatus($this->normalizeStatus($command->status));
            } catch (\InvalidArgumentException $e) {
                throw new \RuntimeException($e->getMessage());
            }
        }

        if ($command->accessType !== null) {
            try {
                $copy->setAccessType($this->normalizeAccessType($command->accessType));
            } catch (\InvalidArgumentException $e) {
                throw new \RuntimeException($e->getMessage());
            }
        }

        if ($command->location !== null) {
            $copy->setLocation($command->location !== '' ? $command->location : null);
        }

        if ($command->condition !== null) {
            $copy->setConditionState($command->condition !== '' ? $command->condition : null);
        }

        $this->entityManager->persist($copy);
        $book->recalculateInventoryCounters();
        $this->entityManager->flush();

        return $copy;
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = strtoupper(trim($status));
        $valid = [
            BookCopy::STATUS_AVAILABLE,
            BookCopy::STATUS_BORROWED,
            BookCopy::STATUS_RESERVED,
            BookCopy::STATUS_MAINTENANCE,
            BookCopy::STATUS_WITHDRAWN
        ];
        
        if (!in_array($normalized, $valid, true)) {
            return BookCopy::STATUS_AVAILABLE;
        }
        
        return $normalized;
    }

    private function normalizeAccessType(string $accessType): string
    {
        $normalized = strtoupper(trim($accessType));
        $valid = [BookCopy::ACCESS_STORAGE, BookCopy::ACCESS_OPEN_STACK, BookCopy::ACCESS_REFERENCE];
        
        if (!in_array($normalized, $valid, true)) {
            return BookCopy::ACCESS_STORAGE;
        }
        
        return $normalized;
    }
}
