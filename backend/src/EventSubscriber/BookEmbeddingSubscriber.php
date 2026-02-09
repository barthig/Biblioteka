<?php
declare(strict_types=1);
namespace App\EventSubscriber;

use App\Entity\Book;
use App\Message\UpdateBookEmbeddingMessage;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

final class BookEmbeddingSubscriber implements EventSubscriber
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $event): void
    {
        $entity = $event->getObject();
        if (!$entity instanceof Book) {
            return;
        }

        $bookId = $entity->getId();
        if ($bookId === null) {
            return;
        }

        $this->bus->dispatch(new UpdateBookEmbeddingMessage($bookId));
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $entity = $event->getObject();
        if (!$entity instanceof Book) {
            return;
        }

        $changeSet = $event->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);
        if (!array_key_exists('title', $changeSet) && !array_key_exists('description', $changeSet)) {
            return;
        }

        $bookId = $entity->getId();
        if ($bookId === null) {
            return;
        }

        $this->bus->dispatch(new UpdateBookEmbeddingMessage($bookId));
    }
}
