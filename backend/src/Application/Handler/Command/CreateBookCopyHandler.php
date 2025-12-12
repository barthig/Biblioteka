<?php
namespace App\Application\Handler\Command;

use App\Application\Command\BookInventory\CreateBookCopyCommand;
use App\Entity\BookCopy;
use App\Repository\BookRepository;
use App\Repository\BookCopyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateBookCopyHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookRepository $bookRepository,
        private readonly BookCopyRepository $bookCopyRepository
    ) {
    }

    public function __invoke(CreateBookCopyCommand $command): BookCopy
    {
        $book = $this->bookRepository->find($command->bookId);
        if (!$book) {
            throw new \RuntimeException('Book not found');
        }

        $inventoryCode = $command->inventoryCode && trim($command->inventoryCode) !== ''
            ? strtoupper(trim($command->inventoryCode))
            : $this->generateInventoryCode();

        if (!preg_match('/^[A-Z0-9\-_.]+$/', $inventoryCode)) {
            throw new \RuntimeException('Invalid inventoryCode format');
        }

        if ($this->bookCopyRepository->findOneBy(['inventoryCode' => $inventoryCode])) {
            throw new \RuntimeException('Inventory code already exists');
        }

        try {
            $copy = (new BookCopy())
                ->setInventoryCode($inventoryCode)
                ->setStatus($this->normalizeStatus($command->status))
                ->setAccessType($this->normalizeAccessType($command->accessType));
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        if ($command->location) {
            $copy->setLocation($command->location);
        }

        if ($command->condition) {
            $copy->setConditionState($command->condition);
        }

        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();
        try {
            $book->addInventoryCopy($copy);
            $this->entityManager->persist($copy);
            $book->recalculateInventoryCounters();
            $this->entityManager->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw new \RuntimeException('Błąd podczas tworzenia egzemplarza');
        }

        return $copy;
    }

    private function generateInventoryCode(): string
    {
        do {
            $code = 'BC' . strtoupper(bin2hex(random_bytes(6)));
            $exists = $this->bookCopyRepository->findOneBy(['inventoryCode' => $code]);
        } while ($exists);

        return $code;
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
