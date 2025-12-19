<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Collection\CreateCollectionCommand;
use App\Entity\BookCollection;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class CreateCollectionHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookRepository $bookRepository,
        private UserRepository $userRepository
    ) {
    }

    public function __invoke(CreateCollectionCommand $command): BookCollection
    {
        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        if (trim($command->name) === '') {
            throw new BadRequestHttpException('Collection name is required');
        }

        $collection = (new BookCollection())
            ->setName($command->name)
            ->setDescription($command->description)
            ->setCuratedBy($user)
            ->setFeatured($command->featured)
            ->setDisplayOrder($command->displayOrder);

        if (!empty($command->bookIds)) {
            foreach ($command->bookIds as $bookId) {
                $book = $this->bookRepository->find($bookId);
                if ($book) {
                    $collection->addBook($book);
                }
            }
        }

        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        return $collection;
    }
}
