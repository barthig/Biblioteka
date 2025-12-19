<?php

namespace App\EventSubscriber;

use App\Service\BookCacheService;
use App\Service\StatisticsCacheService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\Reservation;

/**
 * Automatically invalidate cache when entities are modified
 */
class CacheInvalidationSubscriber implements EventSubscriber
{
    public function __construct(
        private BookCacheService $bookCacheService,
        private StatisticsCacheService $statisticsCacheService
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->invalidateCache($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->invalidateCache($args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->invalidateCache($args);
    }

    private function invalidateCache(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // Invalidate book-related cache
        if ($entity instanceof Book) {
            $this->bookCacheService->invalidateBook($entity->getId());
            $this->bookCacheService->invalidateBooksList();
            $this->statisticsCacheService->invalidateDashboard();
        }

        // Invalidate cache when loans change
        if ($entity instanceof Loan) {
            $this->bookCacheService->invalidateBookAvailability($entity->getBook()->getId());
            $this->statisticsCacheService->invalidateDashboard();
            $this->statisticsCacheService->invalidateUserStats($entity->getUser()->getId());
        }

        // Invalidate cache when reservations change
        if ($entity instanceof Reservation) {
            $this->bookCacheService->invalidateBookAvailability($entity->getBook()->getId());
            $this->statisticsCacheService->invalidateDashboard();
            $this->statisticsCacheService->invalidateUserStats($entity->getUser()->getId());
        }
    }
}
