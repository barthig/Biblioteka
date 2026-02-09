<?php
namespace App\Application\Handler\Command;

use App\Application\Command\BookInventory\UpdateBookCopyCommand;
use App\Entity\BookCopy;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
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
            throw NotFoundException::forBook($command->bookId);
        }

        $copy = $this->bookCopyRepository->find($command->copyId);
        if (!$copy || $copy->getBook()->getId() !== $book->getId()) {
            throw NotFoundException::forEntity('BookCopy', $command->copyId);
        }

        if ($command->status !== null) {
            try {
                $copy->setStatus($this->normalizeStatus($command->status));
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::forField('status', $e->getMessage());
            }
        }

        if ($command->inventoryCode !== null) {
            $inventoryCode = strtoupper(trim($command->inventoryCode));
            if ($inventoryCode === '') {
                throw ValidationException::forRequiredField('inventoryCode');
            }
            if (!preg_match('/^[A-Z0-9\\-_.]+$/', $inventoryCode)) {
                throw ValidationException::forField('inventoryCode', 'Invalid format. Only uppercase letters, digits, hyphens, dots and underscores are allowed.');
            }
            if ($copy->getInventoryCode() !== $inventoryCode) {
                $existing = $this->bookCopyRepository->findOneByInventoryCode($inventoryCode);
                if ($existing && $existing->getId() !== $copy->getId()) {
                    throw ConflictException::duplicateEntry('inventoryCode', $inventoryCode);
                }
                $copy->setInventoryCode($inventoryCode);
            }
        }

        if ($command->accessType !== null) {
            try {
                $copy->setAccessType($this->normalizeAccessType($command->accessType));
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::forField('accessType', $e->getMessage());
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
