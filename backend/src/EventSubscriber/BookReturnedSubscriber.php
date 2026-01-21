<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\BookReturnedEvent;
use App\Service\NotificationService;
use App\Service\ReservationService;
use App\Repository\AuditLogRepository;
use App\Entity\AuditLog;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles actions when a book is returned.
 */
final class BookReturnedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ReservationService $reservationService,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BookReturnedEvent::class => 'onBookReturned'
        ];
    }

    public function onBookReturned(BookReturnedEvent $event): void
    {
        $loan = $event->getLoan();
        
        try {
            // Send notification to user
            if ($event->isOverdue()) {
                $this->notificationService->notifyOverdueReturn($loan);
            } else {
                $this->notificationService->notifyLoanReturned($loan);
            }
            
            // Process waiting reservations
            $book = $loan->getBook();
            if ($book) {
                $this->reservationService->processNextReservation($book);
            }
            
            // Log audit trail
            $auditLog = new AuditLog();
            $auditLog->setAction('return');
            $auditLog->setEntityType('Loan');
            $auditLog->setEntityId($loan->getId());
            $auditLog->setUser($loan->getUser());
            $auditLog->setNewValues(json_encode([
                'bookId' => $loan->getBook()?->getId(),
                'bookTitle' => $loan->getBook()?->getTitle(),
                'wasOverdue' => $event->isOverdue(),
                'returnedAt' => $loan->getReturnedAt()?->format('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            
            $this->auditLogRepository->save($auditLog, true);
            
            $this->logger->info('Book returned successfully', [
                'loanId' => $loan->getId(),
                'userId' => $loan->getUser()?->getId(),
                'bookId' => $loan->getBook()?->getId(),
                'overdue' => $event->isOverdue()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle book returned event', [
                'error' => $e->getMessage(),
                'loanId' => $loan->getId()
            ]);
        }
    }
}
