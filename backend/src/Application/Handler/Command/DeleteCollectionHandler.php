<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Collection\DeleteCollectionCommand;
use App\Repository\CollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class DeleteCollectionHandler
{
    public function __construct(
        private CollectionRepository $collectionRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(DeleteCollectionCommand $command): void
    {
        $collection = $this->collectionRepository->find($command->collectionId);
        if (!$collection) {
            throw new NotFoundHttpException('Collection not found');
        }

        $this->entityManager->remove($collection);
        $this->entityManager->flush();
    }
}
