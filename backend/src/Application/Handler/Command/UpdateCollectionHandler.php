<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Collection\UpdateCollectionCommand;
use App\Entity\BookCollection;
use App\Repository\BookRepository;
use App\Repository\CollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class UpdateCollectionHandler
{
    public function __construct(
        private CollectionRepository $collectionRepository,
        private BookRepository $bookRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(UpdateCollectionCommand $command): BookCollection
    {
        $collection = $this->collectionRepository->find($command->collectionId);
        if (!$collection) {
            throw new NotFoundHttpException('Collection not found');
        }

        if ($command->name !== null) {
            $collection->setName($command->name);
        }
        if ($command->description !== null) {
            $collection->setDescription($command->description);
        }
        if ($command->featured !== null) {
            $collection->setFeatured($command->featured);
        }
        if ($command->displayOrder !== null) {
            $collection->setDisplayOrder($command->displayOrder);
        }

        if (is_array($command->bookIds)) {
            foreach ($collection->getBooks()->toArray() as $book) {
                $collection->removeBook($book);
            }
            foreach ($command->bookIds as $bookId) {
                $book = $this->bookRepository->find($bookId);
                if ($book) {
                    $collection->addBook($book);
                }
            }
        }

        $this->entityManager->flush();

        return $collection;
    }
}
